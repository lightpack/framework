# Lightpack Deploy

> *From your laptop to production in minutes, not hours.*

Deploy and manage Lightpack applications on a remote Ubuntu server. Provision once, deploy daily.

---

## What You Need

- A fresh Ubuntu 22.04 or 24.04 server (any VPS provider)
- Root or sudo SSH access for the initial provision
- An SSH key pair on your local machine (`~/.ssh/id_rsa` or `~/.ssh/id_ed25519`)
- Your Lightpack application code in a Git repository (optional — the server can use an existing clone)

---

## Quick Start

### 1. Create Deploy Configuration

```bash
php console create:config --support=deploy
```

This creates `config/deploy.php` with a sample production environment.

Each environment supports these options:

| Option | Required | Default | Description |
|---|---|---|---|
| `host` | Yes | — | Server IP or hostname |
| `key` | Yes | — | Local SSH private key path (supports `~`) |
| `path` | Yes | — | Absolute app path on the server |
| `repo` | No | — | Git repository URL. Omit if the server already has the code cloned |
| `branch` | No | `main` | Git branch to deploy |
| `hooks` | No | `[]` | Commands to run after each deploy |

### 2. Prepare Environment File

Create `.env.production` in your project root:

```bash
APP_ENV=production
APP_URL=https://yourdomain.com
DB_HOST=127.0.0.1
DB_NAME=lightpack
DB_USER=lightpack
DB_PSWD=your-db-password
```

### 3. Provision the Server

```bash
php console server:provision production
```

Type `yes` to confirm. Takes 5-15 minutes. When done, root SSH is disabled. Only the `deploy` user can access the server.

### 4. Add Deploy Key to GitHub

After provisioning, the deploy user's SSH public key is printed in the terminal. Copy it.

Alternatively, SSH to the server and show it:

```bash
ssh deploy@your-server-ip "cat ~/.ssh/id_ed25519.pub"
```

In your GitHub repo: **Settings > Deploy keys > Add deploy key**. Do **not** allow write access.

### 5. Deploy

```bash
php console app:deploy production
```

Copies your `.env.production` to the server, pulls code, installs dependencies, runs migrations, and reloads PHP-FPM.

### 6. Add Domain

Point your DNS A record to the server IP, then:

```bash
php console server:site:add production --domain=yourdomain.com
```

Omit `--domain` to be prompted interactively.

### 7. Enable HTTPS

```bash
php console server:site:ssl production --domain=yourdomain.com
```

---

## Rolling Back

```bash
php console app:rollback production
```

Go back further with `--steps=3`.

**Important:** Rollback reverts code only. It does not revert database migrations.

---

## Queue Workers

### Setup (Once)

```bash
php console server:queue:setup production
```

With options:

```bash
php console server:queue:setup production --queue=emails,default --workers=4 --cooldown=3600
```

- `--name`: worker group name (default: environment name)
- `--queue`: comma-separated queue names (default: `default`)
- `--workers`: parallel processes (default: `1`)
- `--cooldown`: seconds before voluntary restart to prevent memory leaks (default: `3600`)
- `--stop-wait`: seconds to wait before force-kill on shutdown (default: `60`)

### Multiple Worker Groups

```bash
php console server:queue:setup production --name=default  --queue=default  --workers=4
php console server:queue:setup production --name=emails   --queue=emails   --workers=2
php console server:queue:setup production --name=reports  --queue=reports  --workers=1
```

### Managing Workers

```bash
php console server:queue:start   production
php console server:queue:stop    production
php console server:queue:restart production
php console server:queue:status  production
```

For named workers: `php console server:queue:restart production --name=emails`

### Viewing Worker Logs

```bash
php console server:queue:logs:view production          # last 50 lines
php console server:queue:logs:view production --lines=200
php console server:queue:logs:tail production         # live stream (Ctrl+C to stop)
```

### Restart Workers on Deploy

Add a `hooks` array to your environment config in `config/deploy.php`:

```php
'production' => [
    'host'   => '1.2.3.4',
    'key'    => '~/.ssh/id_rsa',
    'path'   => '/var/www/lightpack',
    'repo'   => 'git@github.com:you/app.git',
    'branch' => 'main',
    'hooks'  => [
        'php console cache:clear',
        'sudo lp-supervisorctl restart lightpack-production:*',
    ],
],
```

### Local Development

```bash
php console jobs:run
```

Press `Ctrl+C` to stop.

---

## Scheduled Tasks

```bash
php console server:schedule:setup production   # install cron job
php console server:schedule:status production    # check if installed
php console server:schedule:remove production    # remove cron job
```

On the server, `php console schedule:events` runs every minute to execute due tasks.

---

## Environment Management

When you deploy, your local `.env.production` is automatically copied to the server as `.env`.

To inspect the remote `.env` without overwriting your local copy:

```bash
php console server:env:pull production
```

Saved to `storage/env/production.env`.

---

## Database

```bash
php console db:backup production                               # download timestamped dump
php console db:restore production --file=backup-2026-01-15.sql  # upload and restore
php console db:create production --db=shopdb --user=shopuser   # new DB + user
```

Use `db:create` when deploying a second application to the same server.

---

## Logs

```bash
php console server:logs:view production --lines=50   # last N lines
php console server:logs:tail production               # live stream
```

---

## Security Checklist

After provisioning:

- [ ] Root SSH login is disabled
- [ ] Password authentication is disabled
- [ ] Firewall is active (only 22, 80, 443 open)
- [ ] Fail2Ban is running
- [ ] Automatic security updates are enabled
- [ ] `.env` is not in Git
- [ ] `.env.*` files are in `.gitignore`
- [ ] GitHub deploy key does not have write access

---

## Troubleshooting

### "Permission denied (publickey)"

Your SSH key is not on the server. Ensure you can log in:

```bash
ssh root@your-server-ip
```

If not, copy your key first:

```bash
ssh-copy-id root@YOUR_SERVER_IP
```

### "sudo: a password is required"

The deploy user is intentionally restricted. For system-level changes, log in as a user with full sudo access.

### OPcache not refreshing

```bash
ssh deploy@your-server-ip "sudo systemctl reload php8.3-fpm"
```

Replace `8.3` with your PHP version.

---

## Multiple Apps on One Server

Provision once, then add separate environments in `config/deploy.php`:

```php
'deploy' => [
    'blog' => [
        'host' => '1.2.3.4',
        'path' => '/var/www/blog',
        'repo' => 'git@github.com:you/blog.git',
        'branch' => 'main',
    ],
    'shop' => [
        'host' => '1.2.3.4',
        'path' => '/var/www/shop',
        'repo' => 'git@github.com:you/shop.git',
        'branch' => 'main',
    ],
],
```

```bash
php console app:deploy blog
php console app:deploy shop
php console server:site:add blog --domain=blog.example.com
php console server:site:add shop --domain=shop.example.com
```

Queue workers must use unique `--name` values:

```bash
php console server:queue:setup blog --name=blog-worker
php console server:queue:setup shop --name=shop-worker
```

---

## Command Reference

### Core

| Command | Description |
|---|---|
| `php console server:provision <env>` | Provision a fresh server |
| `php console app:deploy <env>` | Deploy code |
| `php console app:rollback <env>` | Roll back to previous commit |

### Schedule

| Command | Description |
|---|---|
| `php console server:schedule:setup <env>` | Install cron job |
| `php console server:schedule:remove <env>` | Remove cron job |
| `php console server:schedule:status <env>` | Check cron status |
| `php console schedule:events` | Run due scheduled events (on server) |

### Queue

| Command | Description |
|---|---|
| `php console jobs:run` | Run worker locally |
| `php console jobs:retry` | Retry failed jobs |
| `php console server:queue:setup <env> [options]` | Install worker (once) |
| `php console server:queue:start <env> [--name]` | Start worker |
| `php console server:queue:stop <env> [--name]` | Stop worker |
| `php console server:queue:restart <env> [--name]` | Restart worker |
| `php console server:queue:status <env> [--name]` | Worker status |
| `php console server:queue:logs:view <env> [--name] [--lines=50]` | View worker logs |
| `php console server:queue:logs:tail <env> [--name]` | Stream worker logs |

### Database

| Command | Description |
|---|---|
| `php console db:backup <env>` | Backup database |
| `php console db:restore <env>` | Restore from backup |
| `php console db:create <env> --db=name --user=user` | Create new database + user |
| `php console migrate:up` | Run migrations |
| `php console migrate:down` | Rollback migrations |

### Logs

| Command | Description |
|---|---|
| `php console server:logs:view <env> [--lines=50] [--file]` | View recent logs |
| `php console server:logs:tail <env>` | Stream logs live |

### Sites

| Command | Description |
|---|---|
| `php console server:site:add <env> --domain=` | Add Nginx virtual host |
| `php console server:site:remove <env> --domain=` | Remove Nginx virtual host |
| `php console server:site:ssl <env> --domain=` | Install SSL certificate |

### Environment

| Command | Description |
|---|---|
| `php console server:env:pull <env>` | Download remote .env |

### Server

| Command | Description |
|---|---|
| `php console server:run <env> --cmd="..."` | Run any command on the server (useful for debugging: `migrate:status`, `cache:clear`, etc.) |

---

*Built for developers who want to ship code, not manage infrastructure.*
