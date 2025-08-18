# Synaplan — AI Communication Management Platform

Synaplan is an open-source platform to orchestrate conversations with multiple AI providers across channels (web, email, WhatsApp), with auditing, usage tracking, and vector search.

## 🚀 Dev Setup

### Prerequisites

- **docker compose**
- **npm (of Node.js)** (for frontend dependencies)

#### 1. Download source code
```bash
# Clone or download the repository
git clone https://github.com/orgaralf/synaplan.com.git synaplan/
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
If you're not installing in `/wwwroot/synaplan.com/web/`, update the paths in `web/inc/_confsys.php`:

```php
// Update these values to match your installation path
$devUrl = "http://localhost/your-path/web/";
$liveUrl = "https://your-domain.com/";
```

#### 7. Verify Installation
1. Point your browser to [http://localhost:8080](http://localhost:8080)
2. You should see a login page
3. Login with the default credentials:
   - **Username:** synaplan@synaplan.com
   - **Password:** synaplan

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


