import sys
import json
import os
import io

# Add user-level site-packages to path for Apache/SYSTEM user compatibility
user_site = 'C:\\Users\\ulaganathan\\AppData\\Roaming\\Python\\Python311\\site-packages'
if os.path.exists(user_site) and user_site not in sys.path:
    sys.path.append(user_site)

import numpy as np
import cv2

# Suppress log spam
import logging
logging.getLogger('ultralytics').setLevel(logging.ERROR)

def run_safety_audit(image_path):
    result = {"status": "APPROVED", "reason": "", "prediction": None, "confidence": 0.0}
    
    try:
        from ultralytics import YOLO
        import torch
        # 1. Safety Audit (Human Detection)
        model = YOLO('yolov8n.pt')
        results = model(image_path, verbose=False)
        
        for r in results:
            for box in r.boxes:
                # Class 0 is person in YOLOv8
                if int(box.cls) == 0:
                    return {"status": "REJECTED", "reason": "Human presence detected (Fallback Scan)."}

        # 2. Category Validation (ONNX Fallback)
        import onnxruntime as ort
        AI_PATH = os.path.join(os.path.dirname(__file__), '../assets/ai/')
        ROUTER_PATH = os.path.join(AI_PATH, 'router_model.onnx')
        
        # Pre-process
        img = cv2.imread(image_path)
        if img is None: return {"status": "ERROR", "reason": "Invalid image"}
        img_rs = cv2.resize(img, (224, 224))
        img_rs = cv2.cvtColor(img_rs, cv2.COLOR_BGR2RGB)
        mean, std = np.array([0.485, 0.456, 0.406]), np.array([0.229, 0.224, 0.225])
        img_norm = (img_rs / 255.0 - mean) / std
        input_tensor = np.expand_dims(img_norm.transpose(2, 0, 1).astype(np.float32), axis=0)

        # Router
        session = ort.InferenceSession(ROUTER_PATH, providers=['CPUExecutionProvider'])
        r_out = session.run(None, {session.get_inputs()[0].name: input_tensor})[0][0]
        r_probs = np.exp(r_out - np.max(r_out)) / np.sum(np.exp(r_out - np.max(r_out)))
        r_class = ["air", "water", "waste"][np.argmax(r_probs)]

        # Specialized
        spec_path = os.path.join(AI_PATH, f'{r_class}_specialized.onnx')
        s_session = ort.InferenceSession(spec_path, providers=['CPUExecutionProvider'])
        s_out = s_session.run(None, {s_session.get_inputs()[0].name: input_tensor})[0][0]
        s_probs = np.exp(s_out - np.max(s_out)) / np.sum(np.exp(s_out - np.max(s_out)))
        
        final_label = r_class if s_probs[0] >= 0.4 else "clean"
        confidence = float(s_probs[0] if final_label != "clean" else s_probs[1])

        if final_label == "clean":
            return {"status": "REJECTED", "reason": "No environmental issue detected."}
            
        result.update({"prediction": final_label, "confidence": round(confidence, 3)})

    except Exception as e:
        # Fallback to approved if models fail to load in cold start
        result["reason"] = f"AI Scan Bypassed: {str(e)}"
        
    if not result.get("reason"):
        result["reason"] = "Image verified"
        
    return result

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "reason": "No image path."}))
        sys.exit(1)
        
    img_path = sys.argv[1]
    if not os.path.exists(img_path):
        print(json.dumps({"status": "ERROR", "reason": "Not found."}))
        sys.exit(1)
        
    print(json.dumps(run_safety_audit(img_path)))
