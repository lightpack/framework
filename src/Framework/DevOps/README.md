# Lightpack DevOps

> *From your laptop to production in minutes, not hours.*

This guide covers everything you need to deploy, manage, and operate a Lightpack application on a remote server. If you have ever felt that deploying a PHP application is harder than it should be, this is for you.

---

## What Is This?

Lightpack DevOps is a set of commands built into the framework that handle the boring, repetitive, and error-prone parts of running a modern web application in production:

- **Provisioning** a fresh server with PHP, Nginx, MySQL, and security hardening
- **Deploying** code with zero-downtime git operations
- **Managing** cron jobs, queue workers, and environment files
- **Rolling back** when things go wrong

All from your terminal. No Ansible. No Docker. No Kubernetes.

---

## The Big Picture

Once you have spinned up a new Ubuntu server, you can provision it to run your Lightpack application.

### Provisioning (One Time)

Provisioning will install PHP, Nginx, MySQL, Composer, and configures everything needed for a secure, performant environment.

### Deployment (Every Day)

Deployment will pull the latest changes from Git, installs dependencies, runs migrations, and other steps that combinedly update your application to the latest version.

---

## Before You Begin

### What You Need

- A fresh Ubuntu 22.04 or 24.04 server (DigitalOcean, Vultr, Hetzner, AWS, any VPS)
- Root or sudo SSH access for the initial provision
- An SSH key pair on your local machine (`~/.ssh/id_rsa` or `~/.ssh/id_ed25519`)
- Your Lightpack application code in a Git repository

### One-Time SSH Setup

Before provisioning, your laptop must be able to connect to the server via SSH:

**1. Add your SSH key to the server.** If your provider lets you add a key during server creation (Vultr, DigitalOcean do), skip this. Otherwise:

```bash
ssh-copy-id root@YOUR_SERVER_IP
```

**2. Connect once to accept the host key.** On the very first connection, SSH will ask you to verify the server's fingerprint.

```bash
ssh root@YOUR_SERVER_IP
```

If you see this prompt, type `yes`:

```
The authenticity of host '...' can't be established.
Are you sure you want to continue connecting (yes/no/[fingerprint])? yes
```

After connecting, type `exit`. You will not see this prompt again.

---

## Step-by-Step: Your First Server

### 1. Create Your Deploy Configuration

Generate the deployment config:

```bash
php console create:config --support=deploy
```

This creates `config/deploy.php`. Open it and set your server IP, SSH key, and app path:

```php
'host'    => 'YOUR_SERVER_IP',
'user'    => 'deploy',
'key'     => '~/.ssh/id_rsa',
'repo'    => 'git@github.com:you/app.git',
'path'    => '/var/www/lightpack',
'branch'  => 'main',
'git_host' => 'github.com',  // for SSH key scanning during provisioning
```

**Optional post-deploy hooks** (run after migrations, before PHP-FPM reload):

```php
'hooks' => [
    'php console cache:clear',
    'php console storage:link',
],
```

**Important:** If your provider disables root SSH by default (most do), set the initial SSH user:

```php
'provision_user' => 'ubuntu',  // or 'kubuntu', 'root', etc.
```

The deploy user does not exist yet — it is created by the provisioning script.

### 2. Prepare Your Environment File

Create `.env.production` in your project root. This is your production environment file:

```bash
APP_ENV=production
APP_URL=https://yourdomain.com
DB_HOST=127.0.0.1
DB_NAME=lightpack
DB_USER=lightpack
DB_PSWD=your-db-password
```

During deployment, this file is copied to the server as `.env`. Keep it out of Git.

**It should be listed in your `.gitignore`**.

### 3. Provision the Server

Run:

```bash
php console server:provision production
```

This will:

1. Ask you to confirm that you want to proceed (type `yes`)
2. Copy the provisioning script to your server
3. Install PHP, Nginx, MySQL, Composer, and all required extensions
4. Create the `deploy` user with restricted privileges
5. Harden SSH (disable root login, disable password authentication)
6. Configure the firewall (allow only SSH, HTTP, HTTPS)
7. Fetch credentials and save them to `deploy/credentials/production.txt`

**This takes 10 to 15 minutes.** Go make some coffee 😅.

When it finishes, your server is ready. Root SSH is disabled. The only way in is via the `deploy` user with your SSH key.

### 4. Add the Deploy Key to GitHub

The provisioning script generated an SSH key for the `deploy` user so the server can pull your code from GitHub. You need to add this key to your repository.

**Option 1 — From the credentials file:**
Open `deploy/credentials/production.txt` and find the line that looks like this:

```
ssh-ed25519 AAAAC3NzaC... deploy@production
```

**Option 2 — From the server directly:**
```bash
php console server:key:show production
```

Either way, copy the key. Go to your repository on GitHub: **Settings > Deploy keys > Add deploy key**. Paste the key. Do **not** allow write access.

### 5. Deploy Your Application

Run:

```bash
php console app:deploy production
```

This will:

1. Copy your local `.env.production` to the server as `.env`
2. SSH into the server as the `deploy` user
3. Pull the latest code from Git
4. Run `composer install --no-dev --optimize-autoloader`
5. Run database migrations
6. Report success or failure

**Your application is now live.**

### 6. Set Up Your Domain

Point your domain's DNS A record to your server's IP address. Then add the Nginx site configuration:

```bash
php console server:site:add production --domain=yourdomain.com
```

To remove a site later:

```bash
php console server:site:remove production --domain=yourdomain.com
```

### 7. Get an SSL Certificate

```bash
php console server:site:ssl production --domain=yourdomain.com
```

Or SSH in and run Certbot manually:

```bash
ssh deploy@YOUR_SERVER_IP
sudo certbot --nginx -d yourdomain.com
```

Your site is now served over HTTPS.

### 8. Managing Multiple Domains

You can add as many domains as you want to the **same application**:

```bash
php console server:site:add production --domain=example.com
php console server:site:add production --domain=www.example.com
php console server:site:add production --domain=api.example.com
```

**Important:** `server:site:add` registers a domain with Nginx. It does **not** automatically happen when you point DNS to the server. Every domain must be explicitly added.

**All domains above serve the same app** from the same codebase. This is ideal for:
- Primary domain + `www` alias
- Subdomains (`api.`, `admin.`, `app.`)
- Multiple top-level domains for the same product (`.com`, `.net`)

**If you need separate applications on the same server** (shared hosting style), that is a different workflow not covered by `server:site:add`. You would need to deploy each app to its own directory and configure Nginx manually.

---

## Understanding the Deploy User

During provisioning, a user named `deploy` is created. This user is the only account you will use for day-to-day operations. Here is exactly what it can and cannot do.

### What the Deploy User Can Do

- Read and write files in `/var/www/`
- Pull code from Git
- Run Composer
- Run PHP scripts (migrations, seeders, queue workers)
- Reload Nginx and PHP-FPM (for zero-downtime deployments)
- Add and remove Nginx site configurations
- Run Certbot to obtain SSL certificates
- Manage its own cron jobs
- Read application logs

### What the Deploy User Cannot Do

- Install system packages
- Modify system configuration files
- Create or delete other users
- Read root-only files like `/etc/shadow`
- Stop or start services (only reload)
- Access other users' files

This is by design. The deploy user is powerful enough to run your application but not powerful enough to destroy your server.

### How the Sudo Restriction Works

The provisioning script creates a file at `/etc/sudoers.d/deploy` that looks like this:

```
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
deploy ALL=(ALL) NOPASSWD: /bin/systemctl status nginx
deploy ALL=(ALL) NOPASSWD: /bin/systemctl status php8.3-fpm
deploy ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-nginx-write
deploy ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-nginx-enable
deploy ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-nginx-disable
deploy ALL=(ALL) NOPASSWD: /usr/bin/certbot certonly *
deploy ALL=(ALL) NOPASSWD: /usr/bin/certbot --nginx *
deploy ALL=(ALL) NOPASSWD: /usr/bin/certbot renew
deploy ALL=(ALL) NOPASSWD: /usr/local/sbin/lp-supervisor-write
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl reread
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl update
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl start lightpack-worker:*
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl stop lightpack-worker:*
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart lightpack-worker:*
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl status lightpack-worker:*
```

This is a **whitelist**, not a blanket grant. The `deploy` user can run exactly these commands without a password. Nothing else. Not `apt-get`, not `systemctl restart`, not `bash`. Just service reloads, nginx site management, certbot, and supervisor queue management.

If you need to install a new PHP extension or change a system setting, you SSH in as yourself with sudo, or you provision a new server. The deploy user does not need this power, so it does not have it.

---

## The Deployment Flow

When you run `php console app:deploy production`, here is exactly what happens on the server:

```
ssh deploy@server "cd /var/www/myapp && \
  git fetch origin main && \
  git reset --hard origin/main && \
  composer install --no-dev --optimize-autoloader && \
  php console migrate:up --force && \
  sudo systemctl reload php-fpm"
```

This is a **destructive reset**, not a merge. It discards any local changes and forces the server to match the remote branch exactly. This is intentional. Your server should never have uncommitted changes.

After the deploy, PHP-FPM is automatically reloaded so new code is active immediately.

---

## Rollback

If a deployment breaks something, you can roll back:

```bash
php console app:rollback production
```

This resets the Git history by one commit and reinstalls dependencies. By default it rolls back one commit. To go back further:

```bash
php console app:rollback production --steps=3
```

**Important:** Rollback only reverts code. It does **not** revert database migrations. If your broken deployment included a migration that changed the schema, you must manually handle the database rollback. This is by design. Database changes should be backward-compatible or run in separate, carefully planned migrations.

---

## Changing the Repository URL

If you move your code to a different Git repository, update `repo` in `config/deploy.php` and redeploy. The deploy script will automatically update the remote URL on the server.

**However, the deploy key does not transfer.** GitHub deploy keys are tied to one repository. You must move the key manually:

1. Get the server's public key:
   ```bash
   php console server:key:show production
   ```

2. On GitHub, **remove** the key from the **old** repository (Settings → Deploy keys).

3. Add the same key to the **new** repository (Settings → Deploy keys → Add deploy key).

4. Deploy:
   ```bash
   php console app:deploy production
   ```

---

## Queue Workers

Lightpack uses [Supervisor](http://supervisord.org/) to manage queue workers in production. Supervisor is installed automatically during provisioning. It keeps your workers running, restarts them if they crash, and handles multiple parallel workers cleanly.

### Setup (Once)

After provisioning, register the worker with Supervisor:

```bash
php console server:queue:setup production
```

With options:

```bash
php console server:queue:setup production --queue=emails,default --workers=4 --cooldown=3600
```

- **`--queue`**: comma-separated queue names to process (default: `default`)
- **`--workers`**: number of parallel worker processes (default: `1`)
- **`--cooldown`**: total runtime in seconds before workers restart cleanly (default: `3600`). This prevents memory leaks and ensures new code is picked up after deploys.

### Managing Workers

```bash
php console server:queue:start production
php console server:queue:stop production
php console server:queue:restart production
php console server:queue:status production
```

### Local Development

For local development, run the worker directly in your terminal:

```bash
php console jobs:run
```

This runs in the foreground. Press `Ctrl+C` to stop.

---

## Scheduled Tasks (Cron)

### Setting Up the Scheduler

Lightpack's scheduler runs events every minute. To set it up on the server:

```bash
php console server:schedule:setup production
```

This adds a single line to the deploy user's crontab:

```
* * * * * cd /var/www/myapp && php console schedule:events >> /dev/null 2>&1
```

No sudo. No system-wide cron. Just the deploy user's own scheduled tasks.

### Checking Status

```bash
php console server:schedule:status production
```

Shows whether the cron job is installed and when the next events are due.

### Removing the Scheduler

```bash
php console server:schedule:remove production
```

---

## Environment Management

### Syncing Environments

When you deploy, your local `.env.production` is automatically copied to the server as `.env`. This means:

- Your secrets never touch Git
- You version-control your environment templates locally
- Each environment (production, staging, dev) has its own file

### Pulling the Remote Environment

Download the remote `.env` to your local machine for inspection:

```bash
php console server:env:pull production
```

This saves the file to `storage/env/production.env` with `0600` permissions. It does **not** overwrite your local `.env.production`.

---

## Database Operations

### Backup

```bash
php console db:backup production
```

Creates a timestamped SQL dump and downloads it to `storage/backups/`.

### Restore

```bash
php console db:restore production --file=backup-2026-01-15.sql
```

Uploads a local backup file to the server and restores it. This is destructive. Use with care.

---

## Logs

### View Logs

```bash
php console server:logs:view production --lines=50
```

Shows the last 50 lines of the application log.

### Tail Logs

```bash
php console server:logs:tail production
```

Streams logs in real-time, like `tail -f`.

---

## Security Checklist

After provisioning, verify these are in place:

- [ ] Root SSH login is disabled (`PermitRootLogin no`)
- [ ] Password authentication is disabled (`PasswordAuthentication no`)
- [ ] Firewall is active (`ufw status` shows active)
- [ ] Only ports 22, 80, and 443 are open
- [ ] Fail2Ban is running (`systemctl status fail2ban`)
- [ ] Automatic security updates are enabled
- [ ] Deploy user sudo is restricted to service reloads, nginx sites, and certbot
- [ ] `.env` is not in Git
- [ ] `.env.*` files are in `.gitignore`
- [ ] GitHub deploy key does not have write access

---

## Troubleshooting

### "Permission denied (publickey)"

Your SSH key is not on the server. If this happens during provisioning, ensure you can log in as root with your key:

```bash
ssh root@your-server-ip
```

If that works, provisioning will work. If not, add your key to the server's `authorized_keys` first.

If this happens during deployment, the deploy user's `authorized_keys` may be missing. The provisioning script copies root's keys to the deploy user, but if root had no keys, the deploy user has none either. Fix by copying your key manually:

```bash
ssh root@your-server-ip "mkdir -p /home/deploy/.ssh && cat >> /home/deploy/.ssh/authorized_keys"
# Paste your public key, then Ctrl+D
```

### "sudo: a password is required"

You are trying to run a command that requires root, but the deploy user does not have passwordless sudo for that command. This is correct behavior. If you need to install a package or modify a system file, do it as a user with proper sudo access, not the deploy user.

### "Failed to copy provisioning script"

The server is not reachable, or root SSH is not configured. Verify:

```bash
ping your-server-ip
ssh root@your-server-ip
```

### "Provisioning script not found"

The bash script `src/Framework/DevOps/scripts/provision.sh` is missing from your installation. It ships with the framework, so this should not happen unless files were deleted.

### "Composer not found" during deploy

The provisioning script installs Composer to `/usr/local/bin/composer`. Ensure this directory is in the deploy user's PATH. The provisioning script handles this, but if you manually created a server, you may need to add it.

### OPcache not refreshing

If you deploy new code but see old behavior, PHP's OPcache may be stale. The deploy user can reload PHP-FPM:

```bash
ssh deploy@your-server-ip "sudo systemctl reload php8.3-fpm"
```

This is the only manual sudo command you should ever need during normal operations.

---

## Philosophy

Lightpack DevOps is built on a few simple beliefs:

1. **Simplicity beats complexity.** A 500-line bash script and a handful of PHP commands can do what orchestration tools do, without the learning curve.
2. **Security by default.** The deploy user is intentionally limited. Root access is for provisioning only.
3. **Transparency over magic.** You can read every line of the provisioning script. You can see every SSH command. Nothing is hidden.
4. **Your server, your rules.** This is not a managed platform. You own the server. You decide what runs on it. We just make the common tasks easier.

---

## Command Reference

### Core Commands

| Command | Description |
|---|---|
| `php console server:provision <env>` | Provision a fresh server |
| `php console app:deploy <env>` | Deploy code to server |
| `php console app:rollback <env>` | Roll back to previous commit |

### Schedule Commands

| Command | Description |
|---|---|
| `php console server:schedule:setup <env>` | Install cron job |
| `php console server:schedule:remove <env>` | Remove cron job |
| `php console server:schedule:status <env>` | Check cron status |
| `php console schedule:events` | Run due scheduled events (on server) |

### Queue Commands

| Command | Description |
|---|---|
| `php console jobs:run` | Run worker locally (development) |
| `php console jobs:retry` | Retry failed jobs |
| `php console server:queue:setup <env>` | Install worker under Supervisor (once) |
| `php console server:queue:start <env>` | Start worker on server |
| `php console server:queue:stop <env>` | Stop worker on server |
| `php console server:queue:restart <env>` | Restart worker on server |
| `php console server:queue:status <env>` | Show worker status on server |

### Database Commands

| Command | Description |
|---|---|
| `php console db:backup <env>` | Backup database |
| `php console db:restore <env>` | Restore from backup |
| `php console migrate:up` | Run migrations |
| `php console migrate:down` | Rollback migrations |

### Log Commands

| Command | Description |
|---|---|
| `php console server:logs:view <env>` | View recent logs |
| `php console server:logs:tail <env>` | Stream logs live |

### Site Management Commands

| Command | Description |
|---|---|
| `php console server:site:add <env> --domain=example.com` | Add Nginx virtual host for a domain |
| `php console server:site:remove <env>` | Remove Nginx virtual host |
| `php console server:site:ssl <env>` | Obtain and install SSL certificate |

### Environment Commands

| Command | Description |
|---|---|
| `php console server:env:pull <env>` | Download remote .env for inspection |

### Server Commands

| Command | Description |
|---|---|
| `php console server:run <env> --cmd="..."` | Run any command on the server |
| `php console server:key:show <env>` | Display the deploy user's public SSH key |
| `php console server:config <env> --upload=100M` | Update PHP/Nginx runtime settings |

---

## FAQ

**Q: Can I provision a server that already has stuff installed?**

Yes, but carefully. The provisioning script is idempotent for most steps. It checks if a user exists before creating one, skips swap creation if swap already exists, and reuses passwords if it finds a credentials file. However, it will overwrite Nginx and PHP-FPM configuration files. If you have custom configs, back them up first.

**Q: Can I use this with Laravel Forge, Ploi, or ServerPilot?**

No, and you do not need to. This replaces those tools. If you prefer a managed panel, use it. If you prefer a terminal and full control, use this.

**Q: What about Docker?**

You can use Docker if you want. Lightpack runs fine in containers. These commands are for traditional VPS deployments, which remain the simplest and most cost-effective option for many applications.

**Q: Can I provision multiple servers from the same project?**

Yes. Add multiple environments to `config/deploy.php`:

```php
'environments' => [
    'production' => ['host' => '1.2.3.4', ...],
    'staging'    => ['host' => '5.6.7.8', ...],
],
```

**Q: Where are my credentials stored?**

On the server: `/root/.lightpack-credentials-final` (root-only access, should be deleted after retrieval)

On your machine: `deploy/credentials/<env>.txt` (chmod 0600, only you can read it)

**Q: What if I lose my local credentials file?**

SSH into the server as root (if still enabled) or as a user with sudo, then read `/root/.lightpack-credentials`. If root SSH is disabled and you have no other sudo user, you may need to use your hosting provider's recovery console.

**Q: Can I change the deploy username?**

Yes. Set the `user` key in your deploy config. The provisioning script will use whatever you specify. However, the commands in this guide assume `deploy`. Adjust accordingly.

**Q: Does this work on shared hosting?**

No. You need root SSH access and a full VPS or dedicated server. Shared hosting environments typically do not allow the necessary permissions.

**Q: What about load balancers and multiple servers?**

This is designed for single-server deployments. For multiple servers, run provisioning on each, then configure a load balancer (Nginx, HAProxy, or your cloud provider's load balancer) to distribute traffic. Database should be on a separate server or use a managed database service.

---

## Contributing

Found a bug? Want to add a feature? The provisioning script is a bash file. The orchestration is PHP. Both are straightforward to read and modify. Open a pull request or file an issue.

---

## License

Same as Lightpack. See the project root for details.

---

*Built for developers who want to ship code, not manage infrastructure.*
