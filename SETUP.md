# Setup Guide

This project has two parts that run independently and share one MySQL database:

- `bot/` — a Python (discord.py) bot process, kept alive by systemd
- `web/` — a PHP admin backend, served by Apache (LAMP)

The bot polls the database for work (broadcast jobs, settings, rules) instead of
talking to the PHP app directly, so nothing needs to be exposed between them
beyond the shared database.

---

## 1. Create the Discord application

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications) → **New Application**.
2. **Bot** tab:
   - Click **Reset Token** and copy the token — this is `DISCORD_BOT_TOKEN`. Treat it
     like a password; anyone with it fully controls your bot.
   - Under **Privileged Gateway Intents**, enable **Server Members Intent**
     (required for the welcome message and the broadcast recipient list).
3. **OAuth2 → General** tab:
   - Copy the **Client ID** and **Client Secret** — these become
     `discord.client_id` / `discord.client_secret` in the web config.
   - Add a redirect URL: `https://your-domain.example/oauth_callback.php`
     (must match `discord.redirect_uri` exactly, including scheme).
4. **OAuth2 → URL Generator** (to invite the bot to your server):
   - Scopes: `bot`, `applications.commands`
   - Bot permissions: `Send Messages`, `Embed Links`, `Read Message History`,
     `View Channels` (add more only if you need them)
   - Open the generated URL and add the bot to your server.
5. Get your **Guild (server) ID** and the **welcome channel ID**: enable
   Developer Mode in Discord (User Settings → Advanced), then right-click the
   server / channel → **Copy ID**.

---

## 2. Set up MySQL

Create the database and two accounts with least privilege — the bot only
needs to read/write specific tables, the web app needs a bit more:

```sql
CREATE DATABASE ga_discord CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'ga_discord_bot'@'localhost' IDENTIFIED BY 'CHANGE-ME-BOT';
GRANT SELECT, INSERT, UPDATE ON ga_discord.* TO 'ga_discord_bot'@'localhost';

CREATE USER 'ga_discord_web'@'localhost' IDENTIFIED BY 'CHANGE-ME-WEB';
GRANT SELECT, INSERT, UPDATE, DELETE ON ga_discord.* TO 'ga_discord_web'@'localhost';

FLUSH PRIVILEGES;
```

Load the schema:

```bash
mysql -u root -p ga_discord < database/schema.sql
```

---

## 3. Deploy the bot

```bash
cd bot
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
# edit .env: DISCORD_BOT_TOKEN, DISCORD_GUILD_ID, DB_* (use ga_discord_bot credentials)
```

Run it once by hand to confirm it connects and registers `/rules`:

```bash
python bot.py
```

Then install it as a systemd service so it survives reboots and restarts on
crash. Edit `deploy/ga-discord-bot.service` to match your install path/user,
then:

```bash
sudo useradd -r -s /usr/sbin/nologin ga-discord-bot   # dedicated, unprivileged user
sudo chown -R ga-discord-bot:ga-discord-bot /opt/ga_discord2/bot
sudo chmod 600 /opt/ga_discord2/bot/.env
sudo cp deploy/ga-discord-bot.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now ga-discord-bot
sudo systemctl status ga-discord-bot
```

---

## 4. Deploy the web admin (Apache)

1. Copy `web/includes/config.example.php` to `web/includes/config.php` and
   fill in the DB credentials (`ga_discord_web`) and Discord OAuth values.
   `web/includes/` sits **outside** the Apache document root, so it's never
   directly reachable over HTTP even without the `.htaccess` rules.
2. Point the Apache vhost's `DocumentRoot` at `web/public`, not `web/`:

```apache
<VirtualHost *:443>
    ServerName your-domain.example
    DocumentRoot /opt/ga_discord2/web/public

    <Directory /opt/ga_discord2/web/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/your-domain.example/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/your-domain.example/privkey.pem
</VirtualHost>

<VirtualHost *:80>
    ServerName your-domain.example
    Redirect permanent / https://your-domain.example/
</VirtualHost>
```

   Enable required modules: `sudo a2enmod ssl rewrite headers`. Get a
   certificate with `certbot --apache` if you don't already have one — the
   OAuth cookie is marked `Secure`, so the panel **requires HTTPS** to work.
3. `chmod 640 web/includes/config.php` and make sure it's owned by a user
   Apache can read but the web server user can't write.
4. Restart Apache: `sudo systemctl restart apache2`.

---

## 5. Bootstrap your first admin

Access is granted to anyone holding the `admin_role_id` role in Discord, or
anyone already listed in the `admins` table. On a fresh install neither is
set yet, so seed yourself in directly:

```sql
INSERT INTO admins (discord_id, discord_username) VALUES ('YOUR_DISCORD_USER_ID', 'you');
```

Log in once at `https://your-domain.example/login.php`, then go to
**Settings** and fill in `guild_id` and `admin_role_id` so future admins can
be granted access just by holding that Discord role — no more manual SQL
needed.

---

## 6. Verify everything works

- Join the Discord server with a test account → welcome message appears in
  the configured channel.
- Run `/rules` in Discord → shows what you configured in the admin panel.
- Add a rule in **Rules**, confirm `/rules` reflects it within a few seconds.
- Queue a broadcast to a test server with only your own test accounts in it
  before ever using it on a real member list.
- Check **Logs** for the `member_join` / `broadcast_done` entries.

---

## Security notes

- `bot/.env` and `web/includes/config.php` hold secrets (bot token, OAuth
  client secret, DB passwords) — both are excluded via `.gitignore`, kept
  outside version control, and should be `chmod 600`/`640` on the server.
- The web app never sees the bot token in the browser; it's used only in
  server-side cURL calls from `oauth_callback.php` / `auth.php`.
- All SQL is parameterized (PDO prepared statements / aiomysql placeholders)
  — never concatenate user input into queries.
- All admin-page output is escaped with `htmlspecialchars()`; all
  state-changing POST requests require a CSRF token.
- OAuth login validates the `state` parameter to prevent CSRF on login, and
  authorization is re-checked against Discord (role membership) on every
  login rather than trusted from a stale session claim.
- The bot paces DMs (`~1.5s` apart) in `cogs/broadcast.py` to avoid hitting
  Discord rate limits or getting temporarily blocked from sending DMs.
- Run the bot under a dedicated, unprivileged system user (see the systemd
  unit's `User=` and hardening options).
