# API Key Management Migration Guide

## Overview

This guide helps you migrate from the legacy `.keys` directory structure to a modern, open-source-friendly environment variable system for managing API keys in Synaplan.

## 🎯 Goals Achieved

- ✅ **Centralized Key Management**: All API keys managed from one place
- ✅ **Open Source Ready**: No private keys in repository
- ✅ **Environment Variable Support**: Production-ready configuration
- ✅ **Backward Compatibility**: Existing `.keys` files still work during transition
- ✅ **Security**: Private keys never committed to version control

## 🔄 Migration Options

### Option 1: Automatic Migration (Recommended)

Run the migration script to automatically convert your existing `.keys` files:

```bash
cd web
php migrate_keys.php
```

This will:
- Scan your `.keys` directory
- Create a `.env` file with all your current API keys
- Provide a migration summary
- Show next steps

### Option 2: Manual Setup

1. **Copy the template**:
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** and replace placeholder values with your actual API keys

3. **Test the application** to ensure everything works

## 📁 New File Structure

### Before (Legacy)
```
web/
├── .keys/
│   ├── .openai.txt
│   ├── .groqkey.txt
│   ├── .googlegemini.txt
│   ├── .thehive.txt
│   ├── .bravekey.txt
│   ├── .watoken.txt
│   ├── .11labs.txt
│   └── .aws.txt
```

### After (Modern)
```
web/
├── .env                 # Your actual API keys (NOT committed)
├── .env.example         # Template for new developers (committed)
└── inc/_confkeys.php    # Centralized key management class
```

## 🔑 Environment Variables

The new system uses these standardized environment variable names:

| Service | Environment Variable | Legacy File |
|---------|---------------------|-------------|
| OpenAI | `OPENAI_API_KEY` | `.keys/.openai.txt` |
| Groq | `GROQ_API_KEY` | `.keys/.groqkey.txt` |
| Google Gemini | `GOOGLE_GEMINI_API_KEY` | `.keys/.googlegemini.txt` |
| Anthropic | `ANTHROPIC_API_KEY` | `.keys/.anthropic.txt` |
| TheHive | `THEHIVE_API_KEY` | `.keys/.thehive.txt` |
| ElevenLabs | `ELEVENLABS_API_KEY` | `.keys/.11labs.txt` |
| Brave Search | `BRAVE_SEARCH_API_KEY` | `.keys/.bravekey.txt` |
| WhatsApp | `WHATSAPP_TOKEN` | `.keys/.watoken.txt` |
| AWS | `AWS_CREDENTIALS` | `.keys/.aws.txt` |
| Google OAuth | `GOOGLE_OAUTH_CREDENTIALS` | `.keys/secret1_ralfsai.json` |
| Gmail OAuth | `GMAIL_OAUTH_TOKEN` | `.keys/gmailtoken.json` |

## 🔐 JSON Configuration Files

The migration also handles JSON configuration files that contain OAuth credentials and tokens. These are typically more complex than simple API keys and require special handling.

### Google OAuth Credentials

The `secret1_ralfsai.json` file contains Google OAuth credentials in this format:
```json
{
  "web": {
    "client_id": "...",
    "project_id": "...",
    "auth_uri": "...",
    "token_uri": "...",
    "auth_provider_x509_cert_url": "...",
    "client_secret": "...",
    "javascript_origins": [...]
  }
}
```

### Gmail OAuth Token

The `gmailtoken.json` file contains Gmail OAuth tokens in this format:
```json
{
  "access_token": "...",
  "expires_in": 3599,
  "scope": "...",
  "token_type": "Bearer",
  "created": 1234567890,
  "refresh_token": "..."
}
```

### Migration Steps for JSON Files

1. **Create JSON Templates**:
   ```bash
   cp web/.keys/secret1_ralfsai.json web/.keys/secret1_ralfsai.json.example
   cp web/.keys/gmailtoken.json web/.keys/gmailtoken.json.example
   ```

2. **Sanitize Template Files**:
   - Remove all sensitive values
   - Keep the structure intact
   - Add placeholder values
   - Add helpful comments

3. **Update Environment Variables**:
   Add these to your `.env` file:
   ```env
   # Google OAuth Credentials (JSON)
   GOOGLE_OAUTH_CREDENTIALS='{"web":{"client_id":"YOUR_ACTUAL_CLIENT_ID","project_id":"YOUR_ACTUAL_PROJECT_ID","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token","auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs","client_secret":"YOUR_ACTUAL_CLIENT_SECRET","javascript_origins":["https://app.synaplan.com","https://ralfs.ai","https://wa.metadist.de"]}}'

   # Gmail OAuth Token (JSON)
   GMAIL_OAUTH_TOKEN='{"access_token":"YOUR_ACTUAL_ACCESS_TOKEN","expires_in":3599,"scope":"https://www.googleapis.com/auth/gmail.modify","token_type":"Bearer","created":1739799683,"refresh_token":"YOUR_ACTUAL_REFRESH_TOKEN"}'
   ```

4. **Update Code to Use Environment Variables**:
   ```php
   // In your OAuth configuration class
   class OAuthConfig {
       public static function getGoogleCredentials() {
           $json = getenv('GOOGLE_OAUTH_CREDENTIALS');
           return $json ? json_decode($json, true) : null;
       }

       public static function getGmailToken() {
           $json = getenv('GMAIL_OAUTH_TOKEN');
           return $json ? json_decode($json, true) : null;
       }
   }
   ```

### Updated File Structure

```
web/
├── .env                 # Your actual API keys and JSON credentials (NOT committed)
├── .env.example         # Template for new developers (committed)
├── .keys/              # Legacy directory (NOT committed)
│   ├── secret1_ralfsai.json.example  # Template for Google OAuth (committed)
│   └── gmailtoken.json.example       # Template for Gmail OAuth (committed)
└── inc/
    ├── _confkeys.php   # Centralized key management class
    └── _oauth.php      # New OAuth configuration class
```

### .gitignore Updates

Add these lines to your `.gitignore`:
```
# OAuth JSON files
web/.keys/*.json
!web/.keys/*.json.example
```

## 🔧 Usage in Code

### New Centralized API (Recommended)

```php
// Initialize (done automatically in _confsys.php)
ApiKeys::init();

// Get specific API keys
$openaiKey = ApiKeys::getOpenAI();
$groqKey = ApiKeys::getGroq();
$geminiKey = ApiKeys::getGoogleGemini();

// Use in AI classes
if (ApiKeys::getOpenAI()) {
    $client = OpenAI::client(ApiKeys::getOpenAI());
}
```

### Legacy Global Variables (Still Supported)

The system automatically sets these global variables for backward compatibility:

```php
$GLOBALS['WAtoken']     // WhatsApp token
$GLOBALS['theHiveKey']  // TheHive API key
$GLOBALS['braveKey']    // Brave Search API key
$GLOBALS['OPENAI']      // OpenAI API key
$GLOBALS['openaiKey']   // OpenAI API key (alternative)
```

## 🚀 Production Deployment

### Environment Variables

Set environment variables directly on your production server:

```bash
export OPENAI_API_KEY="sk-your-actual-key"
export GROQ_API_KEY="gsk_your-actual-key"
export GOOGLE_GEMINI_API_KEY="AIza-your-actual-key"
# ... etc
```

### Docker

```dockerfile
ENV OPENAI_API_KEY=sk-your-actual-key
ENV GROQ_API_KEY=gsk_your-actual-key
ENV GOOGLE_GEMINI_API_KEY=AIza-your-actual-key
```

### Apache/Nginx

Add to your virtual host configuration:

```apache
SetEnv OPENAI_API_KEY "sk-your-actual-key"
SetEnv GROQ_API_KEY "gsk_your-actual-key"
```

## 🔄 Priority System

The new system uses a priority-based loading strategy:

1. **Environment Variables** (production) - highest priority
2. **`.env` file** (development) - medium priority  
3. **Legacy `.keys` files** (backward compatibility) - lowest priority

This ensures smooth transition and production readiness.

## ✅ Updated Files

The following files have been updated to use the new system:

- `inc/_confsys.php` - Loads the new configuration system
- `inc/_confkeys.php` - New centralized API key management
- `inc/_aiopenai.php` - Updated to use `ApiKeys::getOpenAI()`
- `inc/_aigroq.php` - Updated to use `ApiKeys::getGroq()`
- `inc/_aigoogle.php` - Updated to use `ApiKeys::getGoogleGemini()`
- `inc/_aithehive.php` - Updated to use `ApiKeys::getTheHive()`
- `inc/_tools.php` - Updated to use `ApiKeys::getBraveSearch()`

## 🧪 Testing

After migration, test your application:

1. **Check API key loading**:
   ```php
   $missing = ApiKeys::validateKeys();
   if (!empty($missing)) {
       echo "Missing keys: " . implode(', ', $missing);
   }
   ```

2. **Test each AI service** to ensure keys are loaded correctly

3. **Verify backward compatibility** by temporarily renaming `.env` to ensure `.keys` fallback works

## 🗂️ For Open Source Distribution

### What to Include in Repository

✅ **Include these files**:
- `.env.example` - Template for developers
- `inc/_confkeys.php` - Key management class
- `inc/_oauth.php` - OAuth configuration class
- `migrate_keys.php` - Migration script
- Updated AI class files
- `web/.keys/*.json.example` - Template JSON files

❌ **Never include**:
- `.env` - Contains actual API keys and credentials
- `.keys/` directory - Contains actual API keys and credentials
- Any `.json` files with real credentials

### .gitignore

Already updated to exclude:
```