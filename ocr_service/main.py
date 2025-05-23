import base64
import io
import json
import logging
import re
from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import JSONResponse
import requests
import httpx

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI()

OLLAMA_API_URL = "http://localhost:11434/api/generate"
MODEL_NAME = "llava:7b" # Or "llava:latest" - ensure this model is pulled by the Dockerfile

def extract_isbns_from_text(text: str) -> list[str]:
    """
    Extracts ISBN-10 and ISBN-13 codes from a given text.
    ISBN-10: Exactly 10 digits, possibly ending with 'X'. Can have hyphens.
    ISBN-13: Exactly 13 digits, always starts with 978 or 979. Can have hyphens.
    """
    # Regex for ISBN-13: Starts with 978 or 979, followed by 9 or 10 digits, allowing for hyphens.
    # It captures the full 13 digits (or 12 + check digit)
    isbn13_pattern = re.compile(r'(97[89][-\s]?\d{1,5}[-\s]?\d{1,7}[-\s]?\d{1,6}[-\s]?\d{1})')
    
    # Regex for ISBN-10: 9 digits followed by a digit or 'X', allowing for hyphens.
    isbn10_pattern = re.compile(r'(\b\d[-\s]?\d{1,5}[-\s]?\d{1,7}[-\s]?\d{1,5}[-\s]?[0-9X])\b')

    found_isbns = []

    # Find ISBN-13
    for match in isbn13_pattern.finditer(text):
        isbn = match.group(1).replace("-", "").replace(" ", "")
        if len(isbn) == 13:
            # Basic validation (can be enhanced with checksum later if needed)
            if isbn.isdigit():
                 found_isbns.append(isbn)

    # Find ISBN-10
    for match in isbn10_pattern.finditer(text):
        isbn_raw = match.group(1).replace("-", "").replace(" ", "")
        if len(isbn_raw) == 10:
            # Basic validation (can be enhanced with checksum)
            if isbn_raw[:-1].isdigit() and (isbn_raw[-1].isdigit() or isbn_raw[-1].upper() == 'X'):
                # Avoid adding duplicates if an ISBN-10 is part of an ISBN-13 already found (unlikely with good extraction)
                is_part_of_isbn13 = False
                for isbn13 in found_isbns:
                    if isbn_raw in isbn13: # Simple check
                        is_part_of_isbn13 = True
                        break
                if not is_part_of_isbn13:
                    found_isbns.append(isbn_raw)
    
    # Remove duplicates if any were added by chance
    return sorted(list(set(found_isbns)))


@app.post("/extract_isbns/")
async def extract_isbns_from_image(file: UploadFile = File(...)):
    logger.info(f"Received file: {file.filename}, content type: {file.content_type}")

    if not file.content_type.startswith("image/"):
        logger.warning("Uploaded file is not an image.")
        raise HTTPException(status_code=400, detail="Invalid file type. Please upload an image.")

    try:
        image_bytes = await file.read()
        logger.info(f"Read {len(image_bytes)} bytes from image file.")
        
        # Encode image to base64
        base64_image = base64.b64encode(image_bytes).decode("utf-8")
        logger.info("Image successfully encoded to base64.")

        prompt_text = (
            "Extract all ISBN-10 and ISBN-13 codes from this image. "
            "List each ISBN code on a new line. "
            "If no ISBN codes are found, respond with the word 'None' only. "
            "Focus on identifying 10-digit or 13-digit numbers that are likely ISBNs. "
            "ISBN-13 codes start with 978 or 979. ISBN-10 codes can end with an X."
        )

        payload = {
            "model": MODEL_NAME,
            "prompt": prompt_text,
            "images": [base64_image],
            "stream": False  # Get the full response at once
        }
        logger.info(f"Sending request to Ollama API with model: {MODEL_NAME}")

        # Using httpx for async request to Ollama
        async with httpx.AsyncClient(timeout=60.0) as client: # Increased timeout
            response = await client.post(OLLAMA_API_URL, json=payload)
        
        logger.info(f"Received response from Ollama API. Status code: {response.status_code}")
        response.raise_for_status()  # Raise an exception for bad status codes (4xx or 5xx)

        ollama_response_data = response.json()
        logger.info(f"Ollama response data: {ollama_response_data}")

        # Extract the text response from LLaVA
        llava_text_output = ollama_response_data.get("response", "").strip()
        logger.info(f"LLaVA text output: '{llava_text_output}'")

        if llava_text_output.lower() == "none":
            logger.info("LLaVA responded with 'None', no ISBNs found.")
            return JSONResponse(content={"isbns": []})

        # Extract ISBNs from LLaVA's text response
        isbns = extract_isbns_from_text(llava_text_output)
        logger.info(f"Extracted ISBNs: {isbns}")

        return JSONResponse(content={"isbns": isbns})

    except httpx.RequestError as e:
        logger.error(f"HTTPX Request error calling Ollama: {e}", exc_info=True)
        raise HTTPException(status_code=503, detail=f"Could not connect to Ollama service: {str(e)}")
    except requests.exceptions.RequestException as e: # Fallback for general request exceptions
        logger.error(f"Request error calling Ollama: {e}", exc_info=True)
        raise HTTPException(status_code=503, detail=f"Error communicating with Ollama service: {str(e)}")
    except HTTPException as e:
        # Re-raise HTTPExceptions if we threw them (like the 400 for bad file type)
        raise e
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"An unexpected error occurred: {str(e)}")

@app.get("/")
async def read_root():
    return {"message": "OCR Service for ISBN Extraction is running."}

if __name__ == "__main__":
    import uvicorn
    # This part is for local debugging, Docker will use the CMD/ENTRYPOINT
    uvicorn.run(app, host="0.0.0.0", port=8001)
