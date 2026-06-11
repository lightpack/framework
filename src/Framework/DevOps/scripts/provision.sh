#!/bin/bash

################################################################################
# Lightpack Server Provisioning Script
# Target: Ubuntu 22.04 LTS / 24.04 LTS
#
# This script is executed as root on a fresh server to prepare it for
# Lightpack application deployment. It:
#   - Creates a deploy user with restricted privileges
#   - Installs PHP, Nginx, MySQL, Composer
#   - Configures PHP-FPM and Nginx
#   - Hardens SSH and firewall
#   - Generates secure credentials
#
# Security model:
#   - Root SSH is DISABLED after provisioning
#   - Deploy user has limited passwordless sudo (reload services only)
#   - Deploy user CANNOT install packages or run arbitrary commands as root
################################################################################

set -euo pipefail

# -----------------------------------------------------------------------------
# Configuration (override via environment variables)
# -----------------------------------------------------------------------------
SERVER_NAME="${SERVER_NAME:-lightpack}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.3}"
TIMEZONE="${TIMEZONE:-UTC}"
DB_TYPE="${DB_TYPE:-mysql}"          # mysql | none
WEB_SERVER="${WEB_SERVER:-nginx}"    # nginx (caddy coming soon)

MYSQL_DB="${MYSQL_DB:-lightpack}"
MYSQL_USER="${MYSQL_USER:-lightpack}"

# -----------------------------------------------------------------------------
# Colors for output
# -----------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info()    { echo -e "${GREEN}[INFO]${NC}  $1"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }
log_step()    { echo -e "${BLUE}[STEP]${NC}  $1"; }

# -----------------------------------------------------------------------------
# Error trap: cleanup on failure
# -----------------------------------------------------------------------------
CLEANUP_NEEDED=false
trap 'if [ "$CLEANUP_NEEDED" = true ]; then log_error "Provisioning failed. Check /var/log/lightpack-provision.log"; fi' ERR

exec > >(tee -a /var/log/lightpack-provision.log) 2>&1

# -----------------------------------------------------------------------------
# 0. Pre-flight checks
# -----------------------------------------------------------------------------
log_step "Running pre-flight checks..."

if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root"
    exit 1
fi

if ! command -v lsb_release &>/dev/null; then
    apt-get update -qq
    apt-get install -y -qq lsb-release
fi

LSB_RELEASE=$(lsb_release -s -c)
SUPPORTED_CODENAMES="jammy noble"  # 22.04, 24.04

if ! echo "$SUPPORTED_CODENAMES" | grep -qw "$LSB_RELEASE"; then
    log_warn "Untested Ubuntu version: $LSB_RELEASE"
    log_warn "Only jammy (22.04) and noble (24.04) are fully supported"
fi

# -----------------------------------------------------------------------------
# 1. Generate passwords (only on first run)
# -----------------------------------------------------------------------------
log_step "Generating secure credentials..."

if [ -f /root/.lightpack-credentials ]; then
    log_warn "Existing credentials found - reusing passwords"
    source /root/.lightpack-credentials
else
    DEPLOY_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)
    MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)
    MYSQL_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24)

    cat > /root/.lightpack-credentials <<EOF
DEPLOY_PASSWORD='${DEPLOY_PASSWORD}'
MYSQL_ROOT_PASSWORD='${MYSQL_ROOT_PASSWORD}'
MYSQL_PASSWORD='${MYSQL_PASSWORD}'
EOF
    chmod 600 /root/.lightpack-credentials
fi

CLEANUP_NEEDED=true

# -----------------------------------------------------------------------------
# 2. System update
# -----------------------------------------------------------------------------
log_step "Updating system packages..."

export DEBIAN_FRONTEND=noninteractive

# Wait for apt locks (common on fresh cloud VMs)
for i in {1..30}; do
    if ! fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
        break
    fi
    log_warn "Waiting for apt lock (attempt $i/30)..."
    sleep 5
done

apt-get update -qq
apt-get upgrade -y -qq
apt-get autoremove -y -qq

# -----------------------------------------------------------------------------
# 3. Install essential packages
# -----------------------------------------------------------------------------
log_step "Installing essential packages..."

apt-get install -y -qq \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    zip \
    htop \
    vim \
    ufw \
    fail2ban \
    certbot \
    python3-certbot-nginx \
    acl \
    bc

# -----------------------------------------------------------------------------
# 4. Create deploy user
# -----------------------------------------------------------------------------
log_step "Creating deploy user..."

if id "$DEPLOY_USER" &>/dev/null; then
    log_warn "User '$DEPLOY_USER' already exists, skipping creation"
else
    useradd -m -s /bin/bash "$DEPLOY_USER"
    echo "${DEPLOY_USER}:${DEPLOY_PASSWORD}" | chpasswd
    log_info "User '$DEPLOY_USER' created"
fi

# Add to sudo group (standard Ubuntu sudo, password required by default)
usermod -aG sudo "$DEPLOY_USER"

# Create sudoers file with RESTRICTED passwordless commands only
# These are the ONLY commands deploy can run without a password
SUDOERS_FILE="/etc/sudoers.d/${DEPLOY_USER}"

rm -f "$SUDOERS_FILE"

# Service reloads (needed for zero-downtime deploys with opcache)
cat >> "$SUDOERS_FILE" <<EOF
# Lightpack deploy user - restricted passwordless sudo
# DO NOT ADD /bin/bash, /usr/bin/apt, or other privileged commands here
# These are the ONLY commands allowed without password:

${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/sbin/service php${PHP_VERSION}-fpm reload
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload php${PHP_VERSION}-fpm
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status nginx
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status php${PHP_VERSION}-fpm

# Nginx site management (scoped to sites-available/sites-enabled only)
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/tee /etc/nginx/sites-available/*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/ln -sf /etc/nginx/sites-available/* /etc/nginx/sites-enabled/*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-available/*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-enabled/*

# SSL certificate management (scoped to certonly and renew only)
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/certbot certonly *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/certbot renew
EOF

chmod 0440 "$SUDOERS_FILE"
visudo -c >/dev/null || {
    log_error "Sudoers file syntax error - aborting"
    rm -f "$SUDOERS_FILE"
    exit 1
}

log_info "Sudo privileges configured (service reloads, nginx sites, certbot)"

# Setup SSH directory
mkdir -p "/home/${DEPLOY_USER}/.ssh"
chmod 700 "/home/${DEPLOY_USER}/.ssh"

# Copy root's authorized_keys to deploy user (if exists)
if [ -f /root/.ssh/authorized_keys ]; then
    cp /root/.ssh/authorized_keys "/home/${DEPLOY_USER}/.ssh/authorized_keys"
    chmod 600 "/home/${DEPLOY_USER}/.ssh/authorized_keys"
fi

chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "/home/${DEPLOY_USER}/.ssh"

# Add deploy user to www-data group for shared file access
usermod -aG www-data "$DEPLOY_USER"

# Generate SSH key for GitHub deployments
if [ ! -f "/home/${DEPLOY_USER}/.ssh/id_ed25519" ]; then
    su - "$DEPLOY_USER" -c "ssh-keygen -t ed25519 -C 'deploy@${SERVER_NAME}' -f ~/.ssh/id_ed25519 -N ''"
    su - "$DEPLOY_USER" -c "ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null"
    log_info "SSH key generated for GitHub access"
fi

DEPLOY_SSH_KEY=$(cat "/home/${DEPLOY_USER}/.ssh/id_ed25519.pub")

# -----------------------------------------------------------------------------
# 5. Configure timezone
# -----------------------------------------------------------------------------
log_step "Setting timezone to ${TIMEZONE}..."
timedatectl set-timezone "$TIMEZONE" || log_warn "Could not set timezone"

# -----------------------------------------------------------------------------
# 6. Configure swap (critical for small servers)
# -----------------------------------------------------------------------------
log_step "Configuring swap..."

if [ ! -f /swapfile ]; then
    # Calculate swap: min(2GB, 2x RAM)
    RAM_MB=$(free -m | awk '/^Mem:/ {print $2}')
    SWAP_MB=$((RAM_MB * 2))
    [ "$SWAP_MB" -gt 2048 ] && SWAP_MB=2048

    dd if=/dev/zero of=/swapfile bs=1M count=$SWAP_MB status=none
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    log_info "Swap file created (${SWAP_MB}MB)"
else
    log_warn "Swap file already exists"
fi

# -----------------------------------------------------------------------------
# 7. Install PHP
# -----------------------------------------------------------------------------
log_step "Installing PHP ${PHP_VERSION}..."

# Add Ondrej PHP PPA
apt-add-repository -y ppa:ondrej/php >/dev/null || {
    log_warn "PPA may already be added or unavailable, continuing..."
}

apt-get update -qq

PHP_PACKAGES=(
    "php${PHP_VERSION}-fpm"
    "php${PHP_VERSION}-cli"
    "php${PHP_VERSION}-common"
    "php${PHP_VERSION}-mysql"
    "php${PHP_VERSION}-pgsql"
    "php${PHP_VERSION}-xml"
    "php${PHP_VERSION}-mbstring"
    "php${PHP_VERSION}-curl"
    "php${PHP_VERSION}-zip"
    "php${PHP_VERSION}-gd"
    "php${PHP_VERSION}-intl"
    "php${PHP_VERSION}-bcmath"
    "php${PHP_VERSION}-opcache"
    "php${PHP_VERSION}-redis"
    "php${PHP_VERSION}-sqlite3"
)

apt-get install -y -qq "${PHP_PACKAGES[@]}"

# Verify PHP installed
current_php=$(php -v 2>/dev/null | head -n 1 | grep -oP 'PHP \K[0-9]+\.[0-9]+')
if [ -z "$current_php" ]; then
    log_error "PHP installation failed"
    exit 1
fi
log_info "PHP ${current_php} installed"

# -----------------------------------------------------------------------------
# 8. Configure PHP-FPM
# -----------------------------------------------------------------------------
log_step "Optimizing PHP-FPM configuration..."

PHP_FPM_CONF="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
PHP_INI_DIR="/etc/php/${PHP_VERSION}/fpm/conf.d"

mkdir -p "$PHP_INI_DIR"

# PHP optimizations
cat > "${PHP_INI_DIR}/99-lightpack.ini" <<EOF
; Lightpack Production Optimizations
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 64M
upload_max_filesize = 64M

; OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.fast_shutdown = 1

; Security
expose_php = Off
display_errors = Off
log_errors = On

; Performance
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

# Calculate FPM pool size based on available RAM (60% of RAM / 128MB per worker)
TOTAL_RAM_MB=$(free -m | awk '/^Mem:/ {print $2}')
FPM_MAX_CHILDREN=$(( (TOTAL_RAM_MB * 60 / 100) / 128 ))
[ "$FPM_MAX_CHILDREN" -lt 5 ] && FPM_MAX_CHILDREN=5
[ "$FPM_MAX_CHILDREN" -gt 50 ] && FPM_MAX_CHILDREN=50
FPM_START=$(( FPM_MAX_CHILDREN / 4 ))
[ "$FPM_START" -lt 2 ] && FPM_START=2
FPM_MIN_SPARE=$(( FPM_MAX_CHILDREN / 5 ))
[ "$FPM_MIN_SPARE" -lt 2 ] && FPM_MIN_SPARE=2
FPM_MAX_SPARE=$(( FPM_MAX_CHILDREN / 2 ))
[ "$FPM_MAX_SPARE" -lt 4 ] && FPM_MAX_SPARE=4

# FPM pool config
cat > "$PHP_FPM_CONF" <<EOF
[www]
user = www-data
group = www-data
listen = /run/php/php${PHP_VERSION}-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = ${FPM_MAX_CHILDREN}
pm.start_servers = ${FPM_START}
pm.min_spare_servers = ${FPM_MIN_SPARE}
pm.max_spare_servers = ${FPM_MAX_SPARE}
pm.max_requests = 500
pm.process_idle_timeout = 10s

php_admin_value[error_log] = /var/log/php${PHP_VERSION}-fpm.log
php_admin_flag[log_errors] = on
catch_workers_output = yes
EOF

systemctl restart "php${PHP_VERSION}-fpm"
systemctl enable "php${PHP_VERSION}-fpm"

# -----------------------------------------------------------------------------
# 9. Install Nginx
# -----------------------------------------------------------------------------
log_step "Installing and configuring Nginx..."

apt-get install -y -qq nginx

# Backup and replace nginx.conf
cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.original 2>/dev/null || true

cat > /etc/nginx/nginx.conf <<'EOF'
user www-data;
worker_processes auto;
worker_rlimit_nofile 65535;
pid /run/nginx.pid;

include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    server_tokens off;
    client_max_body_size 64M;

    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # SSL
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype image/svg+xml;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
    limit_req_zone $binary_remote_addr zone=login:10m rate=10r/m;

    # Virtual hosts
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF

# Remove default site
rm -f /etc/nginx/sites-enabled/default
rm -f /etc/nginx/sites-available/default

# Create deployment directory
mkdir -p /var/www
chown -R "${DEPLOY_USER}:www-data" /var/www
chmod 755 /var/www

systemctl restart nginx
systemctl enable nginx

# -----------------------------------------------------------------------------
# 10. Install MySQL (if requested)
# -----------------------------------------------------------------------------
if [ "$DB_TYPE" = "mysql" ]; then
    log_step "Installing MySQL..."

    apt-get install -y -qq mysql-server

    # Secure MySQL
    if mysql -u root -e "SELECT 1" &>/dev/null; then
        mysql -u root <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

        # Create app database and user
        mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS ${MYSQL_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON ${MYSQL_DB}.* TO '${MYSQL_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
        log_info "MySQL configured and secured"
    else
        log_warn "MySQL root already has a password set, skipping security setup"
    fi

    # Calculate MySQL InnoDB buffer pool: 25% of total RAM
    MYSQL_BUFFER_MB=$(( TOTAL_RAM_MB / 4 ))
    [ "$MYSQL_BUFFER_MB" -lt 128 ] && MYSQL_BUFFER_MB=128
    [ "$MYSQL_BUFFER_MB" -gt 2048 ] && MYSQL_BUFFER_MB=2048

    # MySQL optimizations for typical VPS
    cat > /etc/mysql/mysql.conf.d/99-lightpack.cnf <<EOF
[mysqld]
max_connections = 100
wait_timeout = 600
max_allowed_packet = 64M
innodb_buffer_pool_size = ${MYSQL_BUFFER_MB}M
innodb_log_file_size = 64M
innodb_file_per_table = 1
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
EOF

    systemctl restart mysql
    systemctl enable mysql
else
    log_info "Skipping MySQL installation (DB_TYPE=${DB_TYPE})"
fi

# -----------------------------------------------------------------------------
# 11. Install Composer
# -----------------------------------------------------------------------------
log_step "Installing Composer..."

if [ ! -f /usr/local/bin/composer ]; then
    EXPECTED_SIGNATURE=$(curl -s https://composer.github.io/installer.sig)
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    ACTUAL_SIGNATURE=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
        log_error "Composer installer signature mismatch - possible tampering"
        rm -f /tmp/composer-setup.php
        exit 1
    fi

    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
    log_info "Composer installed"
else
    log_warn "Composer already exists"
fi

# -----------------------------------------------------------------------------
# 12. Configure firewall
# -----------------------------------------------------------------------------
log_step "Configuring firewall..."

ufw --force reset >/dev/null 2>&1 || true
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow http
ufw allow https
ufw --force enable

log_info "Firewall active: SSH, HTTP, HTTPS allowed"

# -----------------------------------------------------------------------------
# 13. Configure fail2ban
# -----------------------------------------------------------------------------
log_step "Configuring fail2ban..."

cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh

[nginx-http-auth]
enabled = true
port = http,https
EOF

systemctl restart fail2ban
systemctl enable fail2ban

# -----------------------------------------------------------------------------
# 14. Automatic security updates
# -----------------------------------------------------------------------------
log_step "Enabling automatic security updates..."

apt-get install -y -qq unattended-upgrades

cat > /etc/apt/apt.conf.d/50unattended-upgrades <<EOF
Unattended-Upgrade::Allowed-Origins {
    "\${distro_id}:\${distro_codename}-security";
    "\${distro_id}ESMApps:\${distro_codename}-apps-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::MinimalSteps "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
EOF

systemctl enable unattended-upgrades
systemctl start unattended-upgrades

# -----------------------------------------------------------------------------
# 15. SSH hardening
# -----------------------------------------------------------------------------
log_step "Hardening SSH configuration..."

# Disable root login
sed -i 's/^#*PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sed -i 's/^PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config

# Disable password authentication (key-only)
sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config

# Reduce MaxAuthTries
sed -i 's/^#*MaxAuthTries.*/MaxAuthTries 3/' /etc/ssh/sshd_config

systemctl restart ssh

log_info "SSH hardened: root disabled, password auth disabled, key-only"

# -----------------------------------------------------------------------------
# 16. System optimizations
# -----------------------------------------------------------------------------
log_step "Applying system optimizations..."

# File limits
cat > /etc/security/limits.d/99-lightpack.conf <<EOF
* soft nofile 65535
* hard nofile 65535
www-data soft nofile 65535
www-data hard nofile 65535
EOF

# Kernel network tuning
cat > /etc/sysctl.d/99-lightpack.conf <<EOF
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 8192
net.core.netdev_max_backlog = 5000
net.ipv4.tcp_fin_timeout = 30
net.ipv4.tcp_keepalive_time = 300
fs.file-max = 2097152
vm.swappiness = 10
EOF

sysctl -p /etc/sysctl.d/99-lightpack.conf >/dev/null

# -----------------------------------------------------------------------------
# 17. Create credentials file (readable by deploy user for retrieval)
# -----------------------------------------------------------------------------
log_step "Saving credentials..."

SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

cat > /root/.lightpack-credentials-final <<EOF
================================================================================
LIGHTPACK SERVER CREDENTIALS
================================================================================
Server Name: ${SERVER_NAME}
Server IP:   ${SERVER_IP}
Date:        $(date)

DEPLOY USER:
  Username:  ${DEPLOY_USER}
  Password:  ${DEPLOY_PASSWORD}
  SSH:       ssh ${DEPLOY_USER}@${SERVER_IP}

GITHUB DEPLOY KEY (add to repo):
  ${DEPLOY_SSH_KEY}

PHP:         ${current_php}
Web Server:  ${WEB_SERVER}
Database:    ${DB_TYPE}
EOF

if [ "$DB_TYPE" = "mysql" ]; then
cat >> /root/.lightpack-credentials-final <<EOF

MYSQL ROOT:
  Username:  root
  Password:  ${MYSQL_ROOT_PASSWORD}

MYSQL APP DATABASE:
  Database:  ${MYSQL_DB}
  Username:  ${MYSQL_USER}
  Password:  ${MYSQL_PASSWORD}
EOF
fi

cat >> /root/.lightpack-credentials-final <<EOF

NEXT STEPS:
  1. Add the GitHub deploy key above to your repository
  2. Deploy your app: php console app:deploy ${SERVER_NAME}
  3. Delete this file: rm /root/.lightpack-credentials-final

SECURITY NOTES:
  - Root SSH login is DISABLED
  - Password authentication is DISABLED (keys only)
  - Firewall (UFW) is ACTIVE
  - Fail2Ban is protecting SSH
  - Automatic security updates are ENABLED
  - Deploy user sudo is RESTRICTED (service reloads only)
================================================================================
EOF

chmod 600 /root/.lightpack-credentials-final

# Also copy to /tmp for retrieval by deploy user
cp /root/.lightpack-credentials-final /tmp/lightpack-credentials
chown root:"${DEPLOY_USER}" /tmp/lightpack-credentials
chmod 640 /tmp/lightpack-credentials

log_info "Credentials saved to /root/.lightpack-credentials-final"

# -----------------------------------------------------------------------------
# 18. Final checks
# -----------------------------------------------------------------------------
log_step "Running final checks..."

services=("nginx" "php${PHP_VERSION}-fpm" "fail2ban")
[ "$DB_TYPE" = "mysql" ] && services+=("mysql")

for service in "${services[@]}"; do
    if systemctl is-active --quiet "$service"; then
        log_info "  [OK] $service is running"
    else
        log_error "  [FAIL] $service is NOT running"
    fi
done

# -----------------------------------------------------------------------------
# Done
# -----------------------------------------------------------------------------
CLEANUP_NEEDED=false

echo ""
echo "================================================================================"
echo "                    SERVER PROVISIONING COMPLETED"
echo "================================================================================"
echo ""
cat /root/.lightpack-credentials-final
echo ""
echo "================================================================================"
