# Synaplan - AI Communication Management Platform

Synaplan is an open-source communication management platform that enables seamless interaction with various AI services through multiple channels. Built with modern PHP and leveraging vector search capabilities, it provides a robust foundation for AI-powered communication and tracking.

## 🚀 Installation

### Prerequisites

- **PHP 8.3+** with the following extensions:
  - `curl`, `json`, `mysqli` (required)
  - `memcached` (recommended for session management)
  - `bcmath`, `bz2`, `gd`, `http`, `imagick` (recommended)
- **MariaDB 11.7+** (required for vector search capabilities)
- **Composer** (for PHP dependencies)
- **npm (of Node.js)** (for frontend dependencies)
- **Web server** (Apache/Nginx)
- **Linux System Tools** (required for audio processing and file operations):
  - `ffmpeg` (for audio/video file processing)
  - `curl` (for external API communications)
  - `wget` (for downloading models and files)

### Step-by-Step Installation

After installation, synaplan sits in "web/", the "www/" directory is our official website.

#### 1. Download and Extract
```bash
# Clone or download the repository
git clone https://github.com/orgaralf/synaplan.com.git
cd synaplan
```

#### 2. Database Setup
```sql
-- Create database and user
CREATE DATABASE synaplan;
CREATE USER 'synaplan'@'localhost' IDENTIFIED BY 'synaplan';
GRANT ALL PRIVILEGES ON synaplan.* TO 'synaplan'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. Load Database Schema
Load all SQL files from the `db-loadfiles/` directory into your MariaDB database. The files can be loaded in any order:

```bash
# Option 1: Using mariadb command line
mariadb -u synaplan -p synaplan < db-loadfiles/*.sql

# Option 2: Using phpMyAdmin or your preferred database tool
# Import each .sql file from the db-loadfiles/ directory
```

**Note:** This will create the default user account for the web frontend:
- **Username:** synaplan@synaplan.com
- **Password:** synaplan

#### 4. Install Dependencies
```bash
# Install PHP dependencies
cd web/
COMPOSER_PROCESS_TIMEOUT=1600 composer install

# Install Node.js dependencies
npm install
```

#### 5. Configure Web Server
Point your web server's document root to the `web/` directory.

Note the security hint about the ".env" file. Disallow that - how is described below.

**Apache Example:**
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/synaplan/web
    
    <Directory /path/to/synaplan/web>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/synaplan/web;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

#### 6. Set File Permissions
```bash
# Create upload directory and set permissions
mkdir -p web/up/
chmod 755 web/up/
chown www-data:www-data web/up/  # Adjust user/group as needed
```

#### 7. Configure Environment
Create a `.env` file in the `web/` directory with your API keys:

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

**⚠️ CRITICAL SECURITY WARNING:** The `.env` file contains sensitive information and **MUST NOT** be accessible via web requests. Ensure your web server configuration blocks access to `.env` files:

**Apache (.htaccess):**
```apache
<Files ".env">
    Require all denied
</Files>
```

**Nginx:**
```nginx
location ~ /\.env {
    deny all;
    return 404;
}
```

**Recommended AI Service:** We recommend [Groq.com](https://groq.com) as a cost-effective, super-fast AI service for production use.

#### 8. Update Configuration Paths
If you're not installing in `/wwwroot/synaplan.com/web/`, update the paths in `web/inc/_confsys.php`:

```php
// Update these values to match your installation path
$devUrl = "http://localhost/your-path/web/";
$liveUrl = "https://your-domain.com/";
```

#### 9. Verify Installation
1. Point your browser to your installation URL
2. You should see a login page
3. Login with the default credentials:
   - **Username:** synaplan@synaplan.com
   - **Password:** synaplan

### Troubleshooting

**Common Issues:**
- **Vector search not working:** Ensure MariaDB 11.7+ is installed
- **Upload errors:** Check `web/up/` directory permissions
- **AI services not responding:** Verify API keys in `.env` file
- **Database connection errors:** Check database credentials and MariaDB service

**For Local Development:**
- If using Ollama for local AI processing, ensure you have a fast GPU
- The sorting prompt requires local processing capabilities

### Next Steps

After successful installation:
1. Change the default password
2. Configure your preferred AI services
3. Set up communication channels (WhatsApp, Gmail, etc.)
4. Customize the web widget for your needs

## 🌟 Key Features

- **Multi-AI Integration**: Connect with leading AI services:
  - Google Gemini
  - OpenAI ChatGPT
  - Ollama (local deployment)
  - Groq (high-performance AI)
  - And more to come!

- **Communication Channels**:
  - WhatsApp integration
  - Web widget for easy embedding
  - Gmail business integration
  - Extensible architecture for additional channels

- **Advanced Search & Storage**:
  - Built-in RAG (Retrieval-Augmented Generation)
  - Vector search powered by MariaDB 11.7+
  - Efficient data management and retrieval

- **Local Audio Processing**:
  - **Whisper.cpp Integration**: Local, offline speech-to-text transcription
  - Support for MP3 and MP4 audio files
  - High-performance audio processing with multiple model options
  - Automatic fallback to external services

## 🚀 Technical Requirements

- PHP 8.4
- MariaDB 11.7 or higher (required for vector search)
- Composer for dependency management
- Local Ollama installation (for local AI processing)
- **Whisper.cpp**: Local audio transcription (included in Docker setup)
- Various API keys for integrated services

## 🛠️ Installation

1. Clone the repository
2. Install dependencies via Composer
3. Configure your database (MariaDB 11.7+)
4. Set up required API keys
5. Configure your communication channels
6. **Whisper.cpp models are automatically downloaded during Docker build**

Detailed installation instructions coming soon!

## 🔌 API Integration

Synaplan provides multiple integration methods:
- RESTful API endpoints
- Web widget for easy embedding
- WhatsApp business integration
- Gmail business account integration

## 🎵 Audio Processing

### Whisper.cpp Integration
Synaplan includes local audio transcription capabilities using [whisper.cpp](https://github.com/ggerganov/whisper.cpp):

- **Local Processing**: No external API calls required for audio transcription
- **Multiple Models**: Support for base, small, medium, and large models
- **High Performance**: Optimized C++ implementation with multi-threading
- **Format Support**: MP3 and MP4 audio files
- **Automatic Fallback**: Graceful fallback to external services if needed

#### Model Options
- **medium** (1.5 GiB): Recommended for production use
- **base** (142 MiB): Faster alternative for development
- **small** (466 MiB): Good balance of speed and accuracy
- **large** (2.9 GiB): Best accuracy for critical applications

#### Testing
Access the whisper test page: `http://localhost/devtests/testwhisper.php`

## 🤝 Contributing

We welcome contributions! Whether it's:
- Bug fixes
- New AI service integrations
- Additional communication channels
- Documentation improvements
- Performance optimizations

Please read our contributing guidelines (coming soon) before submitting pull requests.

## 📝 License

[License details to be added]

## 🔮 Roadmap

- [ ] Additional AI service integrations
- [ ] Enhanced RAG capabilities
- [ ] More communication channels
- [ ] Improved documentation
- [ ] Community-driven features
- [x] Local audio transcription with whisper.cpp

## 📞 Support

For support, feature requests, or to report issues, please:
- Open an issue on GitHub
- Join our community discussions
- Contact our team

## 🌐 Links

- [Documentation](https://www.synaplan.com/docs.php) (coming soon)

---

Built with ❤️ by the Synaplan Team


