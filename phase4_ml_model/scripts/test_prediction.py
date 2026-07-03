from pathlib import Path
import json
import joblib
import pandas as pd
import numpy as np

ROOT_DIR = Path(__file__).resolve().parents[1]

MODEL_PATH = ROOT_DIR / "models" / "main_gagent_model.pkl"
LABEL_ENCODER_PATH = ROOT_DIR / "models" / "label_encoder.pkl"
FEATURE_COLUMNS_PATH = ROOT_DIR / "models" / "feature_columns.json"
METADATA_PATH = ROOT_DIR / "models" / "model_metadata.json"


def load_artifacts():
    if not MODEL_PATH.exists():
        raise FileNotFoundError(f"Model not found: {MODEL_PATH}")

    if not LABEL_ENCODER_PATH.exists():
        raise FileNotFoundError(f"Label encoder not found: {LABEL_ENCODER_PATH}")

    if not FEATURE_COLUMNS_PATH.exists():
        raise FileNotFoundError(f"Feature columns not found: {FEATURE_COLUMNS_PATH}")

    model = joblib.load(MODEL_PATH)
    label_encoder = joblib.load(LABEL_ENCODER_PATH)

    with open(FEATURE_COLUMNS_PATH, "r", encoding="utf-8") as f:
        feature_config = json.load(f)

    metadata = {}
    if METADATA_PATH.exists():
        with open(METADATA_PATH, "r", encoding="utf-8") as f:
            metadata = json.load(f)

    return model, label_encoder, feature_config, metadata


def predict_sample(model, label_encoder, feature_columns, sample_name, sample):
    X = pd.DataFrame([sample])
    X = X[feature_columns]

    prediction_encoded = model.predict(X)[0]
    prediction_label = label_encoder.inverse_transform([prediction_encoded])[0]

    print("\n" + "=" * 70)
    print(f"Sample: {sample_name}")
    print("=" * 70)
    print(f"Predicted friction level: {prediction_label}")

    if hasattr(model, "predict_proba"):
        probabilities = model.predict_proba(X)[0]
        class_labels = label_encoder.classes_

        print("Class probabilities:")
        for label, probability in zip(class_labels, probabilities):
            print(f"- {label}: {probability:.4f}")

        confidence = float(np.max(probabilities))
        print(f"Confidence score: {confidence:.4f}")

    print("Input feature summary:")
    for key, value in sample.items():
        print(f"- {key}: {value}")


def main():
    model, label_encoder, feature_config, metadata = load_artifacts()

    feature_columns = feature_config["main_features"]

    print("Loaded model successfully.")
    if metadata:
        print(f"Model type: {metadata.get('model_type')}")
        print(f"Training rows: {metadata.get('row_count')}")
        print(f"Macro F1: {metadata.get('macro_f1')}")

    low_sample = {
        "flow_type": "landing_navigation",
        "viewport_type": "desktop",
        "task_completed": 1,
        "task_failed": 0,
        "completion_time": 2.5,
        "click_count": 2,
        "scroll_count": 1,
        "keyboard_count": 0,
        "retry_count": 0,
        "error_count": 0,
        "failed_clicks": 0,
        "unnecessary_clicks": 0,
        "path_deviation_score": 0,
        "page_load_time_ms": 800,
        "dom_content_loaded_ms": 600,
        "time_to_first_byte_ms": 120,
        "feedback_delay_ms": 100,
        "interaction_to_next_paint_ms": 80,
        "cumulative_layout_shift": 0.02,
        "error_message_present": 0,
        "error_message_clarity": 5,
        "popup_detected": 0,
        "cookie_banner_detected": 0,
        "overlay_blocks_cta": 0,
    }

    medium_sample = {
        "flow_type": "signup",
        "viewport_type": "tablet",
        "task_completed": 1,
        "task_failed": 0,
        "completion_time": 6.8,
        "click_count": 6,
        "scroll_count": 3,
        "keyboard_count": 0,
        "retry_count": 1,
        "error_count": 1,
        "failed_clicks": 1,
        "unnecessary_clicks": 2,
        "path_deviation_score": 2,
        "page_load_time_ms": 1800,
        "dom_content_loaded_ms": 1300,
        "time_to_first_byte_ms": 350,
        "feedback_delay_ms": 700,
        "interaction_to_next_paint_ms": 350,
        "cumulative_layout_shift": 0.12,
        "error_message_present": 1,
        "error_message_clarity": 3,
        "popup_detected": 1,
        "cookie_banner_detected": 1,
        "overlay_blocks_cta": 0,
    }

    high_sample = {
        "flow_type": "cta_click",
        "viewport_type": "mobile",
        "task_completed": 0,
        "task_failed": 1,
        "completion_time": 14.5,
        "click_count": 12,
        "scroll_count": 7,
        "keyboard_count": 0,
        "retry_count": 4,
        "error_count": 4,
        "failed_clicks": 5,
        "unnecessary_clicks": 6,
        "path_deviation_score": 5,
        "page_load_time_ms": 4200,
        "dom_content_loaded_ms": 3500,
        "time_to_first_byte_ms": 900,
        "feedback_delay_ms": 2200,
        "interaction_to_next_paint_ms": 1200,
        "cumulative_layout_shift": 0.35,
        "error_message_present": 1,
        "error_message_clarity": 1,
        "popup_detected": 1,
        "cookie_banner_detected": 1,
        "overlay_blocks_cta": 1,
    }

    predict_sample(model, label_encoder, feature_columns, "Low friction sample", low_sample)
    predict_sample(model, label_encoder, feature_columns, "Medium friction sample", medium_sample)
    predict_sample(model, label_encoder, feature_columns, "High friction sample", high_sample)


if __name__ == "__main__":
    main()