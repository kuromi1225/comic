# Use an appropriate base image
FROM python:3.10-slim

# Set working directory
WORKDIR /app/ocr_service

# Install curl and bash (for Ollama installation script and entrypoint)
RUN apt-get update && apt-get install -y curl bash

# Install Ollama
RUN curl -fsSL https://ollama.com/install.sh | sh

# Copy application code and requirements file
COPY ./requirements.txt /app/ocr_service/requirements.txt
COPY ./main.py /app/ocr_service/main.py

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Expose the port the FastAPI app will run on
EXPOSE 8001

# Create an entrypoint script
COPY ./entrypoint.sh /app/ocr_service/entrypoint.sh
RUN chmod +x /app/ocr_service/entrypoint.sh

ENTRYPOINT ["/app/ocr_service/entrypoint.sh"]

# CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001"] 
# This will be called by the entrypoint script after Ollama setup
