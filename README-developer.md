# Synaplan Meta AI Framework

A communication layer above various AI models that offers users a different way to interact with AI models and handle data.

## 🎯 Core Tasks

- **Log all messages** (incoming and outgoing)
- **Track usage** of different AI models
- **Enable integration** of processes with different models
- **Provide open and flexible** connections

Synaplan is Open Source and constantly improving. Join us on the geek side or the business side - there's so much work to do!

## 🛠️ Technology Stack

- **PHP 8.3+** with various modules (see `composer.json`)
- **MariaDB 11.7+** (supports vector tables)
- **NodeJS** for testing and data handling
- **Apache 2.4** with php_mod
- **Ollama** for local AI models
- **Docker** for enterprise integrations (including Kubernetes)
- **Various AI APIs**: OpenAI, Anthropic, Gemini, Groq, etc.

## 🚀 Quick Start

### 1. Environment Configuration

Copy the example environment file and configure your settings:

```bash
cp web/.env.example web/.env
```

Edit `web/.env` with your actual values:
- Database credentials
- AI service API keys
- OAuth configurations
- Application settings

**Important**: Never commit your `.env` file to version control!

### 2. Database Setup

The database structure is defined in `synaplan_structure.sql`. Current backups are in `synaplan.sql`.

### 3. Dependencies

Install PHP dependencies:
```bash
cd web
composer install
```

Install Node.js dependencies:
```bash
npm install
```

## 🏗️ Application Architecture

### Interface Loading Flow

The web interface uses a director pattern for routing:

1. **Entry Point**: `/index.php` loads the application
2. **Director**: `snippets/director.php` handles routing logic
3. **Authentication**: Checks user login status and session
4. **Content Loading**: Directs to appropriate snippet based on:
   - Login status
   - URL parameters
   - User permissions

The director determines which content snippet to load (e.g., `c_chat.php`, `c_settings.php`, `c_login.php`).

### Database Structure

The `db-loadfiles/` directory contains essential database components:

#### User Management (`BUSER.sql`)
- User identification and authentication
- Integration types (WhatsApp, Email, Web)
- User profiles with JSON details
- Provider IDs for external services

#### AI Model Configuration (`BMODELS.sql`)
- Pre-configured AI models from various providers
- Pricing information and quality ratings
- Model capabilities (chat, image generation, etc.)
- Service-specific parameters

#### Message System (`BMESSAGES.sql`)
- Complete message history storage
- Message metadata and tracking
- File attachments and processing
- Direction tracking (IN/OUT)
- Language detection and topics

#### Additional Core Tables
- `BMESSAGEMETA.sql`: Extended message metadata
- `BPROMPTS.sql`: Stored prompts and templates
- `BCONFIG.sql`: System configuration
- `BCAPABILITIES.sql`: Feature capabilities

## 🔌 Integration Methods

Synaplan supports 4 ways to interact:

### 1. WhatsApp Webhook
- Business WhatsApp number required
- Register your number with synaplan
- Send messages to `/webhookwa.php`
- Example: See Ralfs.AI implementation

### 2. Gmail Integration
- Register a code word for your configuration
- Send emails to `smart+yourcode@synaplan.com`
- Automatic mail pickup every 30 seconds
- **Status**: Coming soon!

### 3. API Gateway
- Simple REST API for development
- MCP (Model Context Protocol) server
- JSONRPC-based method calls
- **Status**: Ready for use

### 4. Web Interface
- Login via `/index.php`
- Chat interface and configuration settings
- User management and model selection
- **Status**: Ready for use

## 📁 Code Structure

```
web/
├── .env                    # Environment configuration
├── .env.example           # Configuration template
├── index.php              # Main entry point
├── api.php                # API gateway
├── mcp.php                # MCP server
├── webhookwa.php          # WhatsApp webhook
├── inc/                   # Core libraries
│   ├── _ai*.php          # AI provider integrations
│   ├── _central.php      # Central processing
│   ├── _frontend.php     # Frontend utilities
│   └── _*.php            # Other utilities
├── snippets/              # UI components
│   ├── director.php      # Routing logic
│   ├── c_*.php           # Content snippets
│   └── _fileform.php     # File upload forms
├── css/                   # Stylesheets
├── js/                    # JavaScript files
├── up/                    # User uploads
└── vendor/                # Composer dependencies
```

## 🔧 Development Guidelines

### For Junior Developers

1. **Start with the web interface** - it's the easiest to understand
2. **Check the director.php** - understand how routing works
3. **Examine the database structure** - know your data models
4. **Use the .env.example** - always start with the template
5. **Test with local models** - Ollama integration is great for development

### Common Development Tasks

- **Adding new AI providers**: Create new `_ai*.php` files in `inc/`
- **Creating new UI sections**: Add `c_*.php` files in `snippets/`
- **Database changes**: Update structure files and load files
- **API endpoints**: Extend `api.php` with new methods

## 📚 Additional Resources

- Database schema: `synaplan_structure.sql`
- Current backup: `synaplan.sql`
- Environment template: `web/.env.example`
- Composer dependencies: `web/composer.json`
- Node.js dependencies: `web/package.json`

## 🤝 Contributing

Synaplan is open source! We welcome contributions from both technical and business perspectives. Check the code structure, understand the architecture, and start building!


