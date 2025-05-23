#!/bin/bash
set -e

# Start Ollama in the background
echo "Starting Ollama service..."
ollama serve &
OLLAMA_PID=$!

# Wait for Ollama to be ready
echo "Waiting for Ollama to be ready..."
MAX_WAIT=60 # Maximum wait time in seconds
CURRENT_WAIT=0
OLLAMA_READY=false

while [ $CURRENT_WAIT -lt $MAX_WAIT ]; do
  if curl -s http://localhost:11434/ > /dev/null; then
    echo "Ollama is up!"
    OLLAMA_READY=true
    break
  fi
  sleep 2
  CURRENT_WAIT=$((CURRENT_WAIT + 2))
  echo "Still waiting for Ollama... (${CURRENT_WAIT}s / ${MAX_WAIT}s)"
done

if [ "$OLLAMA_READY" = false ]; then
  echo "Ollama failed to start within $MAX_WAIT seconds."
  # Optionally, capture Ollama logs
  # cat /root/.ollama/logs/*
  exit 1
fi

# Pull the LLaVA model
# Check if the model already exists to save time on subsequent runs (optional, good for dev)
MODEL_NAME_VAR="llava:7b" # Ensure this matches main.py
echo "Checking if model ${MODEL_NAME_VAR} exists..."
if ! ollama list | grep -q "${MODEL_NAME_VAR}"; then
  echo "Pulling LLaVA model (${MODEL_NAME_VAR})... This may take a while."
  ollama pull "${MODEL_NAME_VAR}"
  echo "LLaVA model pulled."
else
  echo "Model ${MODEL_NAME_VAR} already exists."
fi

# Start Uvicorn server for the FastAPI application
echo "Starting FastAPI application on port 8001..."
exec uvicorn main:app --host 0.0.0.0 --port 8001 --workers 1 # Use --workers 1 with Ollama to avoid issues

# Keep Ollama running if FastAPI fails (optional, for debugging)
# wait $OLLAMA_PID
