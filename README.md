# Synaplan — AI Communication Management Platform

Synaplan is an open-source platform to orchestrate conversations with multiple AI providers across channels (web, email, WhatsApp), with auditing, usage tracking, and vector search.

## 🚀 Dev Setup

### Prerequisites

- **docker compose**
- **npm (of Node.js)** (for frontend dependencies)

for a dockerless installation, see below.

#### 1. Download source code
```bash
# Clone or download the repository
git clone https://github.com/orgaralf/synaplan.git synaplan/
cd synaplan
```

#### 2. Install dependencies

```bash
docker compose run app composer install
cd public/
npm ci
cd ..
```

### 3. Build and start services

```bash
docker compose build
docker compose up -d
```

#### 4. Set File Permissions
```bash
# Create upload directory and set permissions
mkdir -p public/up/
chmod 755 public/up/
```

#### 5. Configure Environment
Create a `.env` file in the project root directory with your API keys:

```env
# AI Service API Keys (configure at least one)
GROQ_API_KEY=your_groq_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
GOOGLE_GEMINI_API_KEY=your_gemini_api_key_here
OLLAMA_URL=http://localhost:11434  # If using local Ollama

# Database Configuration (if different from defaults)
DB_HOST=localhost
DB_NAME=synaplan
DB_USER=synaplan
DB_PASS=synaplan

# Other Configuration
DEBUG=false
```

**⚠️ CRITICAL SECURITY WARNING:** The `.env` file contains sensitive information and **MUST NOT** be accessible via web requests in production environments. Ensure your web server configuration blocks access to `.env` files:

**Recommended AI Service:** We recommend [Groq.com](https://groq.com) as a cost-effective, super-fast AI service for production use.

#### 6. Update Configuration Paths
If you're not installing in `/wwwroot/synaplan/public/`, update the paths in `public/inc/_confsys.php`:

```php
// Update these values to match your installation path
$devUrl = "http://localhost/your-path/public/";
$liveUrl = "https://your-domain.com/";
```

#### 7. Verify Installation
1. Point your browser to [http://localhost:8080](http://localhost:8080)
2. You should see a login page
3. Login with the default credentials:
   - **Username:** synaplan@synaplan.com
   - **Password:** synaplan

### Install on a standard Linux server (no Docker)

You can also deploy Synaplan on a regular Linux server using Apache, PHP 8.3, and MariaDB 11.7+ (required for vector search). This is ideal when you rely on 3rd‑party AI APIs (OpenAI, Groq, Gemini) instead of local models.

1. Install prerequisites
   - Apache (or any web server) configured to serve the `public/` directory as the document root
   - PHP 8.3 with extensions: `mysqli`, `mbstring`, `curl`, `json`, `zip`
   - MariaDB 11.7+ (for vector search features)
2. Deploy code
   - Place the repository on the server and point your vhost to the `public/` directory
3. Install PHP deps and frontend assets
   - `composer install`
   - `cd public && npm ci && cd ..`
4. Database
   - Create a database (e.g., `synaplan`) and user
   - Import SQL files from `dev/db-loadfiles/` into the database
5. Environment configuration
   - Create a `.env` in the project root with your API keys and DB settings (see the `.env` example above)
6. File permissions
   - Ensure `public/up/` exists with writable permissions for the web server user
7. App URLs
   - Adjust `$devUrl` and `$liveUrl` in `public/inc/_confsys.php` to match your domains/paths
8. Test
   - Open your site (e.g., `https://your-domain/`) and log in with the default credentials above

### Features
- Multiple AI providers (OpenAI, Gemini, Groq, Ollama)
- Channels: Web widget, Gmail business, WhatsApp
- Vector search (MariaDB 11.7+), built-in RAG
- Local audio transcription via whisper.cpp
- Full message logging and usage tracking

### Architecture (brief)
```
public/
├─ index.php            # Entry
├─ snippets/            # UI (routed by snippets/director.php)
├─ inc/                 # Business logic & AI integrations
├─ api.php              # REST gateway
└─ webhookwa.php        # WhatsApp handler
```
Configuration-driven AI selection via `$GLOBALS` and centralized key management in `ApiKeys`.

### API & Integrations
- REST endpoints, embeddable web widget, Gmail and WhatsApp integrations.

### Troubleshooting
- Vector search: ensure MariaDB 11.7+
- Uploads: check `public/up/` permissions
- AI calls: verify API keys in `public/.env`
- DB errors: verify credentials and service status
- COMPOSER_PROCESS_TIMEOUT=1600 necessary? Composer times out on slow drives like WSL2 mounted ones.

### Contributing
PRs welcome for providers, channels, docs, and performance. Start from `web/`, review `snippets/director.php`, and follow existing patterns.

### License
See "LICENSE": Apache 2.0 real open core, because we love it!


