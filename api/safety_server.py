import os
import sys
import logging
from flask import Flask, request, jsonify
from flask_cors import CORS
from PIL import Image
import numpy as np
from ultralytics import YOLO
import onnxruntime as ort
import io
import cv2

app = Flask(__name__)
CORS(app)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Paths - Updated to match project root and your environment
# Paths - Dynamic root detection
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.dirname(SCRIPT_DIR)
AI_PATH = os.path.join(ROOT_DIR, 'assets', 'ai')
MODEL_PATH = os.path.join(AI_PATH, 'yolov8n.pt')
VERSION = "4.0.final" # Full classification support
PORT = 5056

try:
    if os.path.exists(MODEL_PATH):
        logger.info(f"Loading YOLO model from {MODEL_PATH}")
        model = YOLO(MODEL_PATH)
    else:
        logger.error(f"Model file NOT found at {MODEL_PATH}")
        model = None
        
    # Load ONNX models for classification
    logger.info("Loading ONNX classification models...")
    sessions = {
        'router': ort.InferenceSession(os.path.join(AI_PATH, 'router_model.onnx'), providers=['CPUExecutionProvider']),
        'air': ort.InferenceSession(os.path.join(AI_PATH, 'air_specialized.onnx'), providers=['CPUExecutionProvider']),
        'water': ort.InferenceSession(os.path.join(AI_PATH, 'water_specialized.onnx'), providers=['CPUExecutionProvider']),
        'waste': ort.InferenceSession(os.path.join(AI_PATH, 'waste_specialized.onnx'), providers=['CPUExecutionProvider'])
    }
    logger.info("All models loaded successfully.")
except Exception as e:
    logger.error(f"Failed to load models: {e}")
    model = None
    sessions = {}

@app.route('/audit', methods=['POST'])
def audit_image():
    if 'image' not in request.files:
        return jsonify({'error': 'No image provided'}), 400
    
    file = request.files['image']
    image_bytes = file.read()
    image = Image.open(io.BytesIO(image_bytes))
    
    if model:
        # Run inference
        results = model(image)
        
        # Simple safety check logic: 
        # For a sustainability app, we might want to check for specific objects 
        # or just ensure it's a valid photo.
        detections = []
        for r in results:
            for box in r.boxes:
                detections.append({
                    'class': model.names[int(box.cls[0])],
                    'confidence': float(box.conf[0]),
                    'bbox': [float(x) for x in box.xyxy[0]]
                })
        
        # Logging for transparency
        logger.info(f"YOLO Audit: {len(detections)} detections found.")
        
        # --- ONNX Classification Logic ---
        prediction = None
        confidence = 0.0
        
        if sessions:
            try:
                # Pre-processing
                img_cv = cv2.imdecode(np.frombuffer(image_bytes, np.uint8), cv2.IMREAD_COLOR)
                img_rs = cv2.resize(img_cv, (224, 224))
                img_rs = cv2.cvtColor(img_rs, cv2.COLOR_BGR2RGB)
                mean, std = np.array([0.485, 0.456, 0.406]), np.array([0.229, 0.224, 0.225])
                img_norm = (img_rs / 255.0 - mean) / std
                input_tensor = np.expand_dims(img_norm.transpose(2, 0, 1).astype(np.float32), axis=0)
                
                # Router
                r_out = sessions['router'].run(None, {sessions['router'].get_inputs()[0].name: input_tensor})[0][0]
                r_probs = np.exp(r_out - np.max(r_out)) / np.sum(np.exp(r_out - np.max(r_out)))
                r_class = ["air", "water", "waste"][np.argmax(r_probs)]
                
                # Specialized
                s_out = sessions[r_class].run(None, {sessions[r_class].get_inputs()[0].name: input_tensor})[0][0]
                s_probs = np.exp(s_out - np.max(s_out)) / np.sum(np.exp(s_out - np.max(s_out)))
                
                prediction = r_class if s_probs[0] >= 0.4 else "clean"
                confidence = float(s_probs[0] if prediction != "clean" else s_probs[1])
                
                logger.info(f"ONNX Classification: {prediction} ({confidence:.2f})")
                
                if prediction == "clean":
                    return jsonify({
                        'status': 'REJECTED',
                        'reason': 'No environmental issue detected (Image looks clean).',
                        'detections': detections
                    })
                    
            except Exception as e:
                logger.error(f"Classification error: {e}")
        
        # Safety Logic: Reject if humans are detected 
        audit_passed = True
        reason = "Image verified"
        
        for d in detections:
            if d['class'] == 'person':
                audit_passed = False
                reason = "Human presence detected. Please upload only the environmental issue."
                break
        
        if not audit_passed:
            return jsonify({
                'status': 'REJECTED',
                'reason': reason,
                'detections': detections
            })

        return jsonify({
            'status': 'APPROVED',
            'prediction': prediction,
            'confidence': confidence,
            'detections': detections,
            'audit_passed': True,
            'reason': reason
        })
    else:
        logger.warning("Audit attempted but model is not loaded.")
        return jsonify({'status': 'error', 'message': 'Model not loaded'}), 500

if __name__ == '__main__':
    # Try waitress for better performance
    try:
        from waitress import serve
        logger.info(f"Starting AI Server v{VERSION} on port {PORT} (Waitress)...")
        serve(app, host='0.0.0.0', port=PORT)
    except ImportError:
        logger.info(f"Starting AI Server v{VERSION} on port {PORT} (Flask-Dev)...")
        app.run(host='0.0.0.0', port=PORT)
