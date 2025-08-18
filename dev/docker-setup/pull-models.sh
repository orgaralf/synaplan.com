#!/bin/bash

# Start Ollama in the background
ollama serve &

# Wait for Ollama to be ready
echo "Waiting for Ollama to start..."
until curl -f http://localhost:11434/api/tags >/dev/null 2>&1; do
    echo "Waiting for Ollama to be ready..."
    sleep 2
done

echo "Ollama is ready! Pulling models..."

# Pull the 3 models (you can change these to your preferred models)
echo "Pulling llama3.2:3b..."
ollama pull llama3.2:3b

echo "Pulling mistral:7b..."
ollama pull mistral:7b

echo "Pulling codellama:7b..."
ollama pull codellama:7b

echo "All models pulled successfully!"

# Keep the container running
wait 