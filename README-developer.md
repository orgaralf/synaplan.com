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

## 🚀 Quick Start for PHP Developers

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

### 2. Security Configuration

#### .env File Security

Your `.env` file contains sensitive information. Follow these security best practices:

- **Never commit `.env` to version control** - it's already in `.gitignore`
- **Use strong, unique passwords** for database and API keys
- **Rotate API keys regularly** for production environments
- **Restrict file permissions**: `chmod 600 web/.env`
- **Backup securely** - encrypt any backups containing `.env` files

#### Apache Configuration

Create a `.htaccess` file in your `web/` directory to prevent serving sensitive files:

```apache
# Prevent access to sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files ".env.example">
    Order allow,deny
    Deny from all
</Files>

# Prevent access to other sensitive files
<FilesMatch "\.(env|log|sql|bak|backup)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Additional security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

### 3. Database Setup

The database structure is defined in `synaplan_structure.sql`. Current backups are in `synaplan.sql`.

### 4. Dependencies

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

### Core Architecture Overview

Synaplan follows a **modular architecture** with clear separation of concerns:

```
web/
├── index.php              # Main entry point
├── snippets/              # UI Components (View Layer)
├── inc/                   # Business Logic (Controller/Model Layer)
├── api.php                # REST API Gateway
├── webhookwa.php          # WhatsApp Webhook Handler
└── mcp.php                # Model Context Protocol Server
```

### Interface Loading Flow

The web interface uses a **director pattern** for routing:

1. **Entry Point**: `/index.php` loads the application
2. **Director**: `snippets/director.php` handles routing logic
3. **Authentication**: Checks user login status and session
4. **Content Loading**: Directs to appropriate snippet based on:
   - Login status
   - URL parameters
   - User permissions

The director determines which content snippet to load (e.g., `c_chat.php`, `c_settings.php`, `c_login.php`).

## 📁 Code Structure Deep Dive

### `/snippets/` - UI Components (View Layer)

The `snippets/` directory contains all the **user interface components**. Each file represents a different page or section of the application:

#### Core Snippets:
- **`director.php`** - Main routing logic (51 lines)
- **`c_login.php`** - Login form and authentication (22 lines)
- **`c_chat.php`** - Main chat interface (347 lines)
- **`c_settings.php`** - User settings and configuration (366 lines)
- **`c_prompts.php`** - Prompt management interface (503 lines)
- **`c_aimodels.php`** - AI model configuration (229 lines)
- **`c_webwidget.php`** - Web widget configuration (510 lines)
- **`c_mailhandler.php`** - Email integration settings (371 lines)
- **`c_filemanager.php`** - File management interface (606 lines)
- **`c_soundstream.php`** - Audio processing interface (516 lines)
- **`c_statistics.php`** - Usage statistics and analytics (196 lines)
- **`c_tools.php`** - Utility tools and functions (283 lines)
- **`c_inbound.php`** - Inbound message handling (82 lines)
- **`c_preprocessor.php`** - Message preprocessing (84 lines)
- **`c_outprocessor.php`** - Message post-processing (4 lines)
- **`c_docsummary.php`** - Document summarization (229 lines)
- **`c_unknown.php`** - Fallback for unknown routes (8 lines)

#### How Snippets Work:
```php
// Example from director.php
if($contentInc != "login") {
    include("snippets/c_menu.php");           // Always include menu
    include("snippets/c_".$contentInc.".php"); // Include specific content
} else {
    include("snippets/c_login.php");          // Show login form
}
```

### `/inc/` - Business Logic (Controller/Model Layer)

The `inc/` directory contains all the **business logic, AI integrations, and core functionality**:

#### AI Provider Integrations (`_ai*.php`):
- **`_aiopenai.php`** - OpenAI API integration (1,249 lines)
- **`_aigroq.php`** - Groq API integration (426 lines)
- **`_aigoogle.php`** - Google Gemini API integration (1,053 lines)
- **`_aiollama.php`** - Local Ollama integration (474 lines)
- **`_aianthropic.php`** - Anthropic Claude API integration (786 lines)
- **`_aithehive.php`** - TheHive API integration (72 lines)

#### Core System Files:
- **`_central.php`** - Central message processing (995 lines)
- **`_frontend.php`** - Frontend utilities and helpers (1,139 lines)
- **`_basicai.php`** - Basic AI functionality and prompts (671 lines)
- **`_processmethods.php`** - Message processing methods (786 lines)
- **`_tools.php`** - Utility tools and functions (504 lines)
- **`_jsontools.php`** - JSON handling utilities (232 lines)
- **`_listtools.php`** - List and array utilities (232 lines)

#### Configuration Files:
- **`_confdb.php`** - Database configuration (146 lines)
- **`_confsys.php`** - System configuration (73 lines)
- **`_confkeys.php`** - API key management (203 lines)
- **`_confdefaults.php`** - Default configuration values (20 lines)
- **`_inboundconf.php`** - Inbound message configuration (25 lines)

#### Specialized Integrations:
- **`_mail.php`** - Email functionality (74 lines)
- **`_myGMail.php`** - Gmail integration (458 lines)
- **`_wasender.php`** - WhatsApp sending (50 lines)
- **`_oauth.php`** - OAuth authentication (164 lines)
- **`_curler.php`** - cURL utilities (45 lines)
- **`_xscontrol.php`** - Cross-service control (197 lines)
- **`_coreincludes.php`** - Core include management (42 lines)

## 🤖 AI Class Architecture

### How AI Classes Work

Each AI provider has its own class (e.g., `AIOpenAI`, `AIGroq`, `AIGoogle`) that follows a **consistent interface pattern**:

#### Class Structure Example (AIOpenAI):
```php
class AIOpenAI {
    private static $key;
    private static $client;

    // Initialize the AI service
    public static function init() {
        self::$key = ApiKeys::getOpenAI();
        if (!self::$key) return false;
        self::$client = OpenAI::client(self::$key);
        return true;
    }

    // Core AI methods (implemented by all AI classes)
    public static function welcomePrompt($msgArr): array|string|bool { ... }
    public static function sortingPrompt($msgArr, $threadArr): array|string|bool { ... }
    public static function topicPrompt($msgArr, $threadArr, $stream = false): array|string|bool { ... }
    public static function picPrompt($msgArr, $stream = false): array { ... }
    public static function textToSpeech($msgArr, $usrArr): array|bool { ... }
    public static function translateTo($msgArr, $lang='', $sourceText='BTEXT'): array { ... }
    public static function analyzeFile($msgArr, $stream = false): array|string|bool { ... }
}
```

### Configuration-Driven AI Selection

The system uses **configuration-driven AI selection** through the `BasicAI` class:

#### How AI Services Are Selected:
```php
// From _basicai.php - toolPrompt method
$AIT2P = $GLOBALS["AI_TEXT2PIC"]["SERVICE"];      // e.g., "AIOpenAI"
$AIT2Pmodel = $GLOBALS["AI_TEXT2PIC"]["MODEL"];   // e.g., "dall-e-3"
$AIT2PmodelId = $GLOBALS["AI_TEXT2PIC"]["MODELID"]; // e.g., "openai-dall-e-3"

// Dynamic method calling
$msgArr = $AIT2P::picPrompt($msgArr, $stream);
```

#### Configuration Structure:
The `$GLOBALS` array contains AI service configurations:
```php
$GLOBALS["AI_CHAT"]["SERVICE"] = "AIOpenAI";        // Chat service
$GLOBALS["AI_CHAT"]["MODEL"] = "gpt-4";            // Model name
$GLOBALS["AI_CHAT"]["MODELID"] = "openai-gpt-4";   // Unique model ID

$GLOBALS["AI_TEXT2PIC"]["SERVICE"] = "AIOpenAI";   // Image generation
$GLOBALS["AI_TEXT2PIC"]["MODEL"] = "dall-e-3";     // Model name
$GLOBALS["AI_TEXT2PIC"]["MODELID"] = "openai-dall-e-3"; // Unique ID
```

### API Key Management

The `ApiKeys` class provides **centralized API key management**:

#### Key Loading Priority:
1. **Environment variables** (production)
2. **`.env` file** (development)
3. **Legacy `.keys` files** (backward compatibility)

#### Usage Example:
```php
// Get OpenAI API key
$openaiKey = ApiKeys::getOpenAI();

// Get any API key
$groqKey = ApiKeys::get('GROQ_API_KEY');

// Validate all keys
$validKeys = ApiKeys::validateKeys();
```

## 🔧 Development Guidelines

### For Junior Developers

1. **Start with the web interface** - it's the easiest to understand
2. **Check the director.php** - understand how routing works
3. **Examine the database structure** - know your data models
4. **Use the .env.example** - always start with the template
5. **Test with local models** - Ollama integration is great for development

### Common Development Tasks

#### Adding New AI Providers:
1. Create new `_ai*.php` file in `inc/`
2. Implement the standard AI interface methods
3. Add API key configuration to `_confkeys.php`
4. Update model configuration in database
5. Test with the web interface

#### Creating New UI Sections:
1. Add `c_*.php` file in `snippets/`
2. Update `director.php` routing if needed
3. Add menu items in `c_menu.php`
4. Implement any required backend logic in `inc/`

#### Database Changes:
1. Update structure files in `db-loadfiles/`
2. Create migration scripts if needed
3. Update related PHP code
4. Test thoroughly

#### API Endpoints:
1. Extend `api.php` with new methods
2. Follow the existing JSON-RPC pattern
3. Add proper error handling
4. Document the new endpoints

### Code Patterns to Follow

#### Message Processing Pattern:
```php
public static function processMessage($msgArr, $stream = false): array {
    // 1. Initialize AI service
    if (!self::init()) {
        return ['error' => 'AI service not available'];
    }
    
    // 2. Prepare messages
    $messages = self::prepareMessages($msgArr);
    
    // 3. Call AI API
    try {
        $response = self::$client->chat()->create([
            'model' => $model,
            'messages' => $messages
        ]);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
    
    // 4. Process response
    $result = self::processResponse($response);
    
    // 5. Return formatted result
    return $result;
}
```

#### Configuration Access Pattern:
```php
// Always use the ApiKeys class for API keys
$apiKey = ApiKeys::getOpenAI();

// Use $GLOBALS for service configuration
$service = $GLOBALS["AI_CHAT"]["SERVICE"];
$model = $GLOBALS["AI_CHAT"]["MODEL"];

// Use BasicAI for prompts
$prompt = BasicAI::getAprompt("tools:sort", "en");
```

## 📚 Additional Resources

- Database schema: `synaplan_structure.sql`
- Current backup: `synaplan.sql`
- Environment template: `web/.env.example`
- Composer dependencies: `web/composer.json`
- Node.js dependencies: `web/package.json`

## 🤝 Contributing

Synaplan is open source! We welcome contributions from both technical and business perspectives. Check the code structure, understand the architecture, and start building!

### Getting Started with Development:
1. **Fork the repository**
2. **Set up your development environment** using the steps above
3. **Pick an issue** from the GitHub issues
4. **Create a feature branch** for your work
5. **Follow the coding patterns** established in the codebase
6. **Test thoroughly** before submitting a pull request
7. **Document your changes** in the appropriate README files


