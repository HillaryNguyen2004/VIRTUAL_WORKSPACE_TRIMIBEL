from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import base64, cv2, numpy as np
from insightface.app import FaceAnalysis

app = FastAPI()

# ctx_id=-1 for CPU; 0 for GPU if available
fa = FaceAnalysis(name="buffalo_l")
fa.prepare(ctx_id=-1, det_size=(640, 640))

class Verify2Req(BaseModel):
    image_a_base64: str
    image_b_base64: str

def b64_to_bgr(b64: str) -> np.ndarray:
    if "," in b64:
        b64 = b64.split(",", 1)[1]
    try:
        raw = base64.b64decode(b64)
    except Exception:
        raise HTTPException(400, "Invalid base64")
    arr = np.frombuffer(raw, np.uint8)
    img = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    if img is None:
        raise HTTPException(400, "Invalid image bytes")
    return img

def embedding(img: np.ndarray) -> np.ndarray:
    faces = fa.get(img)
    if not faces:
        raise HTTPException(422, "No face detected")
    faces.sort(key=lambda f: (f.bbox[2]-f.bbox[0])*(f.bbox[3]-f.bbox[1]), reverse=True)
    return faces[0].embedding.astype(np.float32)

def cosine_sim(a: np.ndarray, b: np.ndarray) -> float:
    a = a / (np.linalg.norm(a) + 1e-9)
    b = b / (np.linalg.norm(b) + 1e-9)
    return float(np.dot(a, b))

@app.post("/verify-two")
def verify_two(req: Verify2Req):
    imgA = b64_to_bgr(req.image_a_base64)
    imgB = b64_to_bgr(req.image_b_base64)

    embA = embedding(imgA)
    embB = embedding(imgB)

    sim = cosine_sim(embA, embB)

    # Tune threshold. Start around 0.85–0.90 for InsightFace cosine similarity.
    threshold = 0.82
    match = sim >= threshold
    
    print(f"Similarity: {sim}, Match: {match}")

    return {"match": match, "similarity": sim, "threshold": threshold}