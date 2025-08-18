@echo off
setlocal enabledelayedexpansion

echo 🐳 SynaPlan Docker Development Environment
echo ==========================================

REM Function to display usage
:usage
if "%1"=="help" goto :help
if "%1"=="" goto :help
goto :eof

:help
echo Usage: %0 [COMMAND]
echo.
echo Commands:
echo   build     - Build the Docker images
echo   start     - Start all services
echo   stop      - Stop all services
echo   restart   - Restart all services
echo   logs      - Show logs from all services
echo   clean     - Stop and remove all containers, networks, and volumes
echo   shell     - Access the PHP application container shell
echo   db        - Access the MySQL database
echo   status    - Show status of all services
echo   help      - Show this help message
echo.
goto :eof

REM Function to check if Docker is running
:check_docker
docker info >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not running. Please start Docker Desktop first.
    exit /b 1
)
goto :eof

REM Function to build images
:build
echo 🔨 Building Docker images...
docker-compose build
if errorlevel 1 (
    echo ❌ Build failed!
    exit /b 1
)
echo ✅ Build completed successfully!
goto :eof

REM Function to start services
:start
echo 🚀 Starting services...
docker-compose up -d
if errorlevel 1 (
    echo ❌ Failed to start services!
    exit /b 1
)
echo ✅ Services started successfully!
echo.
echo 📱 Access your application:
echo    - Main App: http://localhost:8080
echo    - phpMyAdmin: http://localhost:8081
echo    - Ollama API: http://localhost:11434
echo.
echo 📊 Check status: %0 status
echo 📋 View logs: %0 logs
goto :eof

REM Function to stop services
:stop
echo 🛑 Stopping services...
docker-compose down
echo ✅ Services stopped successfully!
goto :eof

REM Function to restart services
:restart
echo 🔄 Restarting services...
docker-compose down
docker-compose up -d
echo ✅ Services restarted successfully!
goto :eof

REM Function to show logs
:logs
echo 📋 Showing logs...
docker-compose logs -f
goto :eof

REM Function to clean everything
:clean
echo 🧹 Cleaning up Docker environment...
echo ⚠️  This will remove all containers, networks, and volumes!
set /p confirm="Are you sure? (y/N): "
if /i "!confirm!"=="y" (
    docker-compose down -v
    docker system prune -f
    echo ✅ Cleanup completed!
) else (
    echo ❌ Cleanup cancelled.
)
goto :eof

REM Function to access shell
:shell
echo 🐚 Accessing PHP application container shell...
docker-compose exec app bash
goto :eof

REM Function to access database
:db
echo 🗄️  Accessing MySQL database...
docker-compose exec db mysql -u synaplan_user -p synaplan
goto :eof

REM Function to show status
:status
echo 📊 Service Status:
echo ==================
docker-compose ps
echo.
echo 💾 Resource Usage:
echo ==================
docker stats --no-stream
goto :eof

REM Main script logic
if "%1"=="build" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :build
) else if "%1"=="start" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :start
) else if "%1"=="stop" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :stop
) else if "%1"=="restart" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :restart
) else if "%1"=="logs" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :logs
) else if "%1"=="clean" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :clean
) else if "%1"=="shell" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :shell
) else if "%1"=="db" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :db
) else if "%1"=="status" (
    call :check_docker
    if errorlevel 1 exit /b 1
    call :status
) else (
    call :help
) 