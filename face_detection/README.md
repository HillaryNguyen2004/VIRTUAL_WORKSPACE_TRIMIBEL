# Face Recognition Microservice

This service uses **InsightFace** (Buffalo_L model) to provide high-accuracy face verification APIs. It is designed to work with the Laravel backend.

## 🚀 Prerequisites

- **Python 3.8+**
- **pip** (Python package manager)
- **Visual Studio C++ Build Tools** (Windows only, required for some dependencies)

## 📦 Installation

1.  **Navigate to the directory:**

    ```bash
    cd face_detection
    ```

2.  **Create a virtual environment (Recommended):**

    ```bash
    python -m venv venv

    # Windows
    venv\Scripts\activate

    # Mac/Linux
    source venv/bin/activate
    ```

3.  **Install dependencies:**
    ```bash
    pip install fastapi uvicorn insightface onnxruntime opencv-python pydantic
    ```
    _Note: Use `onnxruntime-gpu` instead of `onnxruntime` if you have a compatible NVIDIA GPU._

## 🏃 Running the Service

Start the server using Uvicorn. The default port expected by the Laravel `FaceService` is `8001`.

```bash
uvicorn app:app --host 0.0.0.0 --port 8001
```

The service will download the `buffalo_l` model automatically on the first run (approx 300MB).

## 🔌 API Endpoints

### `POST /verify-two`

Compares two base64-encoded images to determine if they belong to the same person.

**Request Body:**

```json
{
    "image_a_base64": "data:image/jpeg;base64,/9j/4AAQSk...",
    "image_b_base64": "data:image/jpeg;base64,/9j/4AAQSk..."
}
```

**Response:**

```json
{
    "match": true,
    "similarity": 0.8923,
    "threshold": 0.88
}
```

## ⚙️ Configuration

- **Model**: `buffalo_l` (InsightFace)
- **Similarity Threshold**: `0.82` (Adjustable in `app.py`)
- **Port**: 8001 (Configurable via `--port` flag)

## 🛠 Troubleshooting

**Error: `No face detected`**

- Ensure images are clear and have good lighting.
- Ensure the base64 string is valid (data URI prefix is handled automatically).

**Error: `onnxruntime` import failed**

- Use `pip install onnxruntime` (CPU) or `pip install onnxruntime-gpu` (GPU).
- On Windows, ensure VC++ Redistributable is installed.
