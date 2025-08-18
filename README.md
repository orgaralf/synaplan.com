# Synaplan — AI Communication Management Platform

Synaplan is an open-source platform to orchestrate conversations with multiple AI providers across channels (web, email, WhatsApp), with auditing, usage tracking, and vector search.

### Requirements
- PHP 8.3+ (extensions: curl, json, mysqli; optional: memcached, bcmath, bz2, gd, http, imagick)
- MariaDB 11.7+
- Composer, Node.js/npm
- Web server (Apache or Nginx)
- CLI tools: ffmpeg, curl, wget

### Quick Start
1) Clone
```bash
git clone https://github.com/your-repo/synaplan.git
cd synaplan
```

2) Database
```sql
CREATE DATABASE synaplan;
CREATE USER 'synaplan'@'localhost' IDENTIFIED BY 'synaplan';
GRANT ALL PRIVILEGES ON synaplan.* TO 'synaplan'@'localhost';
FLUSH PRIVILEGES;
```
Load schema (any order):
```bash
mariadb -u synaplan -p synaplan < db-loadfiles/*.sql
```

3) Dependencies
```bash
cd web
COMPOSER_PROCESS_TIMEOUT=1600 composer install
npm install
```

4) Environment
```bash
cp .env.example .env
```
Edit `web/.env` for DB and API keys (OpenAI, Groq, Gemini, Ollama). Keep it private and block access:
```apache
# web/.htaccess
<Files ".env">Require all denied</Files>
```
```nginx
location ~ /\.env { deny all; return 404; }
```

5) Web server
- Point document root to `web/`
- If paths differ, update `web/inc/_confsys.php` (`$devUrl`, `$liveUrl`)

6) Uploads
```bash
mkdir -p web/up && chmod 755 web/up
```

7) Login
- URL: your site root
- User: `synaplan@synaplan.com`
- Pass: `synaplan`

### Features
- Multiple AI providers (OpenAI, Gemini, Groq, Ollama)
- Channels: Web widget, Gmail business, WhatsApp
- Vector search (MariaDB 11.7+), built-in RAG
- Local audio transcription via whisper.cpp
- Full message logging and usage tracking

### Architecture (brief)
```
web/
├─ index.php            # Entry
├─ snippets/            # UI (routed by snippets/director.php)
├─ inc/                 # Business logic & AI integrations
├─ api.php              # REST gateway
└─ webhookwa.php        # WhatsApp handler
```
Configuration-driven AI selection via `$GLOBALS` and centralized key management in `ApiKeys`.

### API & Integrations
- REST endpoints, embeddable web widget, Gmail and WhatsApp integrations.

### Docker
Docker setup will be simplified and summarized here shortly

### Troubleshooting
- Vector search: ensure MariaDB 11.7+
- Uploads: check `web/up/` permissions
- AI calls: verify API keys in `web/.env`
- DB errors: verify credentials and service status

### Contributing
PRs welcome for providers, channels, docs, and performance. Start from `web/`, review `snippets/director.php`, and follow existing patterns.

### License
[To be added]


