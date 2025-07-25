# SynaPlan Docker Development Environment

This directory contains all the necessary files to run the SynaPlan PHP application in a Docker containerized environment for development purposes.

## 🚀 Quick Start

### Prerequisites

- Docker Desktop installed and running
- Docker Compose (usually included with Docker Desktop)
- At least 4GB of available RAM

### Getting Started

1. **Navigate to the dockerdev directory:**
   ```bash
   cd dockerdev
   ```

2. **Copy the environment file:**
   ```bash
   cp .env.docker .env
   ```

3. **Edit the .env file** with your actual API keys and configuration:
   ```bash
   # Edit .env file with your API keys
   nano .env
   ```

4. **Start the development environment:**
   ```bash
   docker-compose up -d
   ```

5. **Access the application:**
   - **Main Application:** http://localhost:8080
   - **phpMyAdmin:** http://localhost:8081
   - **Ollama API:** http://localhost:11434

## 📁 File Structure

```
dockerdev/
├── Dockerfile              # PHP application container definition
├── docker-compose.yml      # Multi-container orchestration
├── .dockerignore          # Files to exclude from Docker build
├── .env.docker            # Environment variables template
└── README.md              # This file
```

## 🐳 Services

The Docker Compose setup includes the following services:

### 1. **app** (PHP Application)
- **Port:** 8080
- **Base Image:** PHP 8.2 with Apache
- **Features:**
  - All PHP extensions required by the application
  - Composer for dependency management
  - Apache with mod_rewrite enabled
  - Hot-reload for development (volume mounted)

### 2. **db** (MySQL Database)
- **Port:** 3306
- **Base Image:** MySQL 8.0
- **Features:**
  - Automatic database initialization with SQL files
  - Persistent data storage
  - Pre-configured user and database

### 3. **ollama** (Local AI Models)
- **Port:** 11434
- **Base Image:** Ollama latest
- **Features:**
  - Local AI model hosting
  - Compatible with the application's AI features
  - Persistent model storage

### 4. **phpmyadmin** (Database Management)
- **Port:** 8081
- **Base Image:** phpMyAdmin latest
- **Features:**
  - Web-based MySQL administration
  - Pre-configured connection to the database

## 🔧 Configuration

### Environment Variables

Copy `.env.docker` to `.env` and configure the following:

#### Required API Keys:
- `OPENAI_API_KEY` - OpenAI API key
- `GROQ_API_KEY` - Groq API key  
- `GOOGLE_GEMINI_API_KEY` - Google Gemini API key
- `ANTHROPIC_API_KEY` - Anthropic API key

#### Optional API Keys:
- `THEHIVE_API_KEY` - TheHive integration
- `ELEVENLABS_API_KEY` - Text-to-speech
- `BRAVE_SEARCH_API_KEY` - Search functionality
- `WHATSAPP_TOKEN` - WhatsApp Business API
- `AWS_CREDENTIALS` - AWS services

#### OAuth Configuration:
- `GOOGLE_OAUTH_CREDENTIALS` - Google OAuth JSON
- `GMAIL_OAUTH_TOKEN` - Gmail OAuth token

### Database Configuration

The database is automatically configured with:
- **Database:** synaplan
- **User:** synaplan_user
- **Password:** synaplan_password
- **Root Password:** root_password

## 🛠️ Development Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs

# Specific service
docker-compose logs app
docker-compose logs db
```

### Rebuild Application
```bash
docker-compose build app
docker-compose up -d app
```

### Access Container Shell
```bash
# PHP application container
docker-compose exec app bash

# Database container
docker-compose exec db mysql -u synaplan_user -p synaplan
```

### Install Composer Dependencies
```bash
docker-compose exec app composer install
```

### Run Database Migrations
```bash
# The database is automatically initialized with SQL files
# Check logs if you need to see the initialization process
docker-compose logs db
```

## 🔍 Troubleshooting

### Common Issues

1. **Port Already in Use:**
   ```bash
   # Check what's using the port
   netstat -ano | findstr :8080
   
   # Change ports in docker-compose.yml if needed
   ```

2. **Database Connection Issues:**
   ```bash
   # Check if database is running
   docker-compose ps db
   
   # Check database logs
   docker-compose logs db
   ```

3. **Permission Issues:**
   ```bash
   # Fix file permissions
   docker-compose exec app chown -R www-data:www-data /var/www/html
   ```

4. **Memory Issues:**
   - Increase Docker Desktop memory allocation
   - Stop unnecessary containers: `docker system prune`

### Reset Everything
```bash
# Stop and remove all containers, networks, and volumes
docker-compose down -v

# Remove all images
docker system prune -a

# Start fresh
docker-compose up -d
```

## 📊 Monitoring

### Check Service Status
```bash
docker-compose ps
```

### Monitor Resource Usage
```bash
docker stats
```

### View Application Logs
```bash
# Apache logs
docker-compose exec app tail -f /var/log/apache2/access.log
docker-compose exec app tail -f /var/log/apache2/error.log
```

## 🔒 Security Notes

- This setup is for **development only**
- Never use default passwords in production
- Keep API keys secure and never commit them to version control
- Consider using Docker secrets for production deployments

## 🚀 Production Considerations

For production deployment:

1. Use environment-specific Dockerfiles
2. Implement proper SSL/TLS certificates
3. Use Docker secrets for sensitive data
4. Set up proper backup strategies
5. Configure monitoring and logging
6. Use a reverse proxy (nginx/traefik)
7. Implement health checks
8. Set up CI/CD pipelines

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Docker and application logs
3. Ensure all prerequisites are met
4. Verify environment variable configuration 