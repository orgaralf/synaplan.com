#!/bin/bash

# Script to download whisper.cpp models
# Based on https://huggingface.co/ggerganov/whisper.cpp

set -e

# Create models directory if it doesn't exist
MODELS_DIR="/var/www/html/inc/whispermodels"
mkdir -p "$MODELS_DIR"

echo "Downloading whisper.cpp models to $MODELS_DIR"

# Function to download model
download_model() {
    local model_name=$1
    local model_url=$2
    local model_file="$MODELS_DIR/$model_name.bin"
    
    if [ -f "$model_file" ]; then
        echo "Model $model_name already exists, skipping download"
        return
    fi
    
    echo "Downloading $model_name..."
    cd "$MODELS_DIR"
    curl -L -o "$model_file" "$model_url"
    
    if [ -f "$model_file" ]; then
        echo "Successfully downloaded $model_name"
        ls -lh "$model_file"
    else
        echo "Failed to download $model_name"
        exit 1
    fi
}

# Download medium model (1.5 GiB)
# SHA: fd9727b6e1217c2f614f9b698455c4ffd82463b4
download_model "medium" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.bin"

# Download medium.en model (1.5 GiB) - English only, faster
# SHA: 8c30f0e44ce9560643ebd10bbe50cd20eafd3723
download_model "medium.en" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.en.bin"

# Download base model (142 MiB) - smaller, faster
# SHA: 465707469ff3a37a2b9b8d8f89f2f99de7299dac
download_model "base" "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-base.bin"

echo "All models downloaded successfully!"
echo "Available models:"
ls -lh "$MODELS_DIR"/*.bin

# Set proper permissions
chown -R www-data:www-data "$MODELS_DIR"
chmod -R 755 "$MODELS_DIR" 