#!/bin/bash

# SynaPlan Docker Development Build Script

set -e

echo "🐳 SynaPlan Docker Development Environment"
echo "=========================================="

# Function to display usage
usage() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  build     - Build the Docker images"
    echo "  start     - Start all services"
    echo "  stop      - Stop all services"
    echo "  restart   - Restart all services"
    echo "  logs      - Show logs from all services"
    echo "  clean     - Stop and remove all containers, networks, and volumes"
    echo "  shell     - Access the PHP application container shell"
    echo "  db        - Access the MySQL database"
    echo "  status    - Show status of all services"
    echo "  help      - Show this help message"
    echo ""
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo "❌ Docker is not running. Please start Docker Desktop first."
        exit 1
    fi
}

# Function to build images
build() {
    echo "🔨 Building Docker images..."
    docker-compose build
    echo "✅ Build completed successfully!"
}

# Function to start services
start() {
    echo "🚀 Starting services..."
    docker-compose up -d
    echo "✅ Services started successfully!"
    echo ""
    echo "📱 Access your application:"
    echo "   - Main App: http://localhost:8080"
    echo "   - phpMyAdmin: http://localhost:8081"
    echo "   - Ollama API: http://localhost:11434"
    echo ""
    echo "📊 Check status: $0 status"
    echo "📋 View logs: $0 logs"
}

# Function to stop services
stop() {
    echo "🛑 Stopping services..."
    docker-compose down
    echo "✅ Services stopped successfully!"
}

# Function to restart services
restart() {
    echo "🔄 Restarting services..."
    docker-compose down
    docker-compose up -d
    echo "✅ Services restarted successfully!"
}

# Function to show logs
logs() {
    echo "📋 Showing logs..."
    docker-compose logs -f
}

# Function to clean everything
clean() {
    echo "🧹 Cleaning up Docker environment..."
    echo "⚠️  This will remove all containers, networks, and volumes!"
    read -p "Are you sure? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose down -v
        docker system prune -f
        echo "✅ Cleanup completed!"
    else
        echo "❌ Cleanup cancelled."
    fi
}

# Function to access shell
shell() {
    echo "🐚 Accessing PHP application container shell..."
    docker-compose exec app bash
}

# Function to access database
db() {
    echo "🗄️  Accessing MySQL database..."
    docker-compose exec db mysql -u synaplan_user -p synaplan
}

# Function to show status
status() {
    echo "📊 Service Status:"
    echo "=================="
    docker-compose ps
    echo ""
    echo "💾 Resource Usage:"
    echo "=================="
    docker stats --no-stream
}

# Main script logic
case "${1:-help}" in
    build)
        check_docker
        build
        ;;
    start)
        check_docker
        start
        ;;
    stop)
        check_docker
        stop
        ;;
    restart)
        check_docker
        restart
        ;;
    logs)
        check_docker
        logs
        ;;
    clean)
        check_docker
        clean
        ;;
    shell)
        check_docker
        shell
        ;;
    db)
        check_docker
        db
        ;;
    status)
        check_docker
        status
        ;;
    help|*)
        usage
        ;;
esac 