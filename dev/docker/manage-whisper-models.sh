#!/bin/bash

# Whisper.cpp Model Management Script
# Helps manage whisper models in the Synaplan application

set -e

MODELS_DIR="/var/www/html/inc/whispermodels"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Function to download a model
download_model() {
    local model_name=$1
    local model_url=$2
    local model_file="$MODELS_DIR/$model_name.bin"
    
    if [ -f "$model_file" ]; then
        print_warning "Model $model_name already exists"
        read -p "Do you want to overwrite it? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            return
        fi
    fi
    
    print_status "Downloading $model_name..."
    mkdir -p "$MODELS_DIR"
    cd "$MODELS_DIR"
    
    if curl -L -o "$model_file" "$model_url"; then
        print_status "Successfully downloaded $model_name"
        ls -lh "$model_file"
    else
        print_error "Failed to download $model_name"
        return 1
    fi
}

# Function to list models
list_models() {
    print_header "Available Models"
    
    if [ ! -d "$MODELS_DIR" ]; then
        print_warning "Models directory does not exist: $MODELS_DIR"
        return
    fi
    
    local models=($(find "$MODELS_DIR" -name "*.bin" -type f))
    
    if [ ${#models[@]} -eq 0 ]; then
        print_warning "No models found in $MODELS_DIR"
        return
    fi
    
    echo "Models in $MODELS_DIR:"
    for model in "${models[@]}"; do
        local size=$(du -h "$model" | cut -f1)
        local filename=$(basename "$model")
        echo "  ✅ $filename ($size)"
    done
    
    echo
    print_status "Total models: ${#models[@]}"
}

# Function to clean up models
cleanup_models() {
    print_header "Cleanup Models"
    
    if [ ! -d "$MODELS_DIR" ]; then
        print_warning "Models directory does not exist"
        return
    fi
    
    local models=($(find "$MODELS_DIR" -name "*.bin" -type f))
    
    if [ ${#models[@]} -eq 0 ]; then
        print_warning "No models to clean up"
        return
    fi
    
    echo "Found ${#models[@]} model(s):"
    for model in "${models[@]}"; do
        local size=$(du -h "$model" | cut -f1)
        local filename=$(basename "$model")
        echo "  - $filename ($size)"
    done
    
    read -p "Do you want to remove all models? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -f "$MODELS_DIR"/*.bin
        print_status "All models removed"
    else
        print_status "Cleanup cancelled"
    fi
}

# Function to show model information
show_model_info() {
    print_header "Model Information"
    
    echo "Available models from https://huggingface.co/ggerganov/whisper.cpp:"
    echo
    echo "| Model | Size | SHA | Description |"
    echo "|-------|------|-----|-------------|"
    echo "| tiny | 75 MiB | bd577a113a864445d4c299885e0cb97d4ba92b5f | Fastest, least accurate |"
    echo "| base | 142 MiB | 465707469ff3a37a2b9b8d8f89f2f99de7299dac | Good balance |"
    echo "| small | 466 MiB | 55356645c2b361a969dfd0ef2c5a50d530afd8d5 | Better accuracy |"
    echo "| medium | 1.5 GiB | fd9727b6e1217c2f614f9b698455c4ffd82463b4 | Recommended |"
    echo "| large-v3 | 2.9 GiB | ad82bf6a9043ceed055076d0fd39f5f186ff8062 | Best accuracy |"
    echo
    echo "Note: Models with .en suffix are English-only and faster."
}

# Function to download all recommended models
download_recommended() {
    print_header "Downloading Recommended Models"
    
    # Download medium model (recommended for production)
    download_model "medium" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.bin"
    
    # Download base model (faster alternative)
    download_model "base" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-base.bin"
    
    print_status "Recommended models downloaded"
}

# Function to check system requirements
check_system() {
    print_header "System Check"
    
    # Check if whisper binary exists
    if command -v whisper >/dev/null 2>&1; then
        print_status "Whisper binary found: $(which whisper)"
    else
        print_error "Whisper binary not found. Please ensure whisper.cpp is installed."
    fi
    
    # Check available disk space
    local available_space=$(df -h "$MODELS_DIR" 2>/dev/null | tail -1 | awk '{print $4}')
    if [ -n "$available_space" ]; then
        print_status "Available disk space: $available_space"
    fi
    
    # Check available memory
    local total_mem=$(free -h | grep Mem | awk '{print $2}')
    print_status "Total memory: $total_mem"
    
    # Check if models directory exists
    if [ -d "$MODELS_DIR" ]; then
        print_status "Models directory exists: $MODELS_DIR"
    else
        print_warning "Models directory does not exist: $MODELS_DIR"
    fi
}

# Main script logic
case "${1:-}" in
    "list")
        list_models
        ;;
    "download")
        case "${2:-}" in
            "medium")
                download_model "medium" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.bin"
                ;;
            "base")
                download_model "base" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-base.bin"
                ;;
            "small")
                download_model "small" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-small.bin"
                ;;
            "recommended")
                download_recommended
                ;;
            *)
                print_error "Usage: $0 download [medium|base|small|recommended]"
                exit 1
                ;;
        esac
        ;;
    "cleanup")
        cleanup_models
        ;;
    "info")
        show_model_info
        ;;
    "check")
        check_system
        ;;
    *)
        echo "Whisper.cpp Model Management Script"
        echo
        echo "Usage: $0 [command]"
        echo
        echo "Commands:"
        echo "  list                    - List installed models"
        echo "  download [model]        - Download specific model"
        echo "    models: medium, base, small, recommended"
        echo "  cleanup                 - Remove all models"
        echo "  info                    - Show model information"
        echo "  check                   - Check system requirements"
        echo
        echo "Examples:"
        echo "  $0 list"
        echo "  $0 download medium"
        echo "  $0 download recommended"
        echo "  $0 cleanup"
        ;;
esac 