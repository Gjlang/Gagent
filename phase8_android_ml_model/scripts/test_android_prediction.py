from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict
from xml.parsers.expat import model

import joblib
import numpy as np
import pandas as pd


ROOT_DIR = Path(__file__).resolve().parents[1]
MODELS_DIR = ROOT_DIR / "models"

MODEL_PATH = MODELS_DIR / "android_appium_model.pkl"
PREPROCESSOR_PATH = MODELS_DIR / "android_preprocessing_pipeline.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "android_label_encoder.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "android_feature_columns.json"


LOW_SAMPLE = {
    "flow_type": "login",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 1,
    "task_failed": 0,
    "completion_time": 5.0,
    "click_count": 3,
    "scroll_count": 0,
    "keyboard_count": 2,
    "retry_count": 0,
    "error_count": 0,
    "failed_clicks": 0,
    "unnecessary_clicks": 0,
    "path_deviation_score": 0.05,
    "app_launch_time_ms": 800,
    "screen_load_time_ms": 600,
    "feedback_delay_ms": 250,
    "interaction_response_time_ms": 300,
    "finish_time_ms": 5200,
    "error_message_present": 0,
    "error_message_clarity": -1,
    "popup_detected": 0,
    "overlay_blocks_action": 0,
    "timeout_occurred": 0,
    "crash_detected": 0,
    "anr_detected": 0,
}

MEDIUM_SAMPLE = {
    "flow_type": "signup",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 1,
    "task_failed": 0,
    "completion_time": 12.0,
    "click_count": 7,
    "scroll_count": 2,
    "keyboard_count": 4,
    "retry_count": 1,
    "error_count": 1,
    "failed_clicks": 1,
    "unnecessary_clicks": 2,
    "path_deviation_score": 0.35,
    "app_launch_time_ms": 1300,
    "screen_load_time_ms": 1700,
    "feedback_delay_ms": 1200,
    "interaction_response_time_ms": 1500,
    "finish_time_ms": 12500,
    "error_message_present": 1,
    "error_message_clarity": 1,
    "popup_detected": 1,
    "overlay_blocks_action": 0,
    "timeout_occurred": 0,
    "crash_detected": 0,
    "anr_detected": 0,
}

HIGH_SAMPLE = {
    "flow_type": "form_submit",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 0,
    "task_failed": 1,
    "completion_time": 27.0,
    "click_count": 15,
    "scroll_count": 4,
    "keyboard_count": 5,
    "retry_count": 4,
    "error_count": 5,
    "failed_clicks": 5,
    "unnecessary_clicks": 6,
    "path_deviation_score": 0.85,
    "app_launch_time_ms": 2500,
    "screen_load_time_ms": 5200,
    "feedback_delay_ms": 4300,
    "interaction_response_time_ms": 5000,
    "finish_time_ms": 30000,
    "error_message_present": 1,
    "error_message_clarity": 0,
    "popup_detected": 1,
    "overlay_blocks_action": 1,
    "timeout_occurred": 1,
    "crash_detected": 0,
    "anr_detected": 1,
}


def load_artifacts():
    required = [
        MODEL_PATH,
        PREPROCESSOR_PATH,
        LABEL_ENCODER_PATH,
        FEATURE_COLUMNS_PATH,
    ]

    missing = [path for path in required if not path.exists()]

    if missing:
        raise FileNotFoundError(
            "Missing Android model files:\n"
            + "\n".join(str(path) for path in missing)
            + "\nRun scripts\\train_android_models.py first."
        )

    model = joblib.load(MODEL_PATH)
    preprocessor = joblib.load(PREPROCESSOR_PATH)
    label_encoder = joblib.load(LABEL_ENCODER_PATH)

    feature_payload = json.loads(FEATURE_COLUMNS_PATH.read_text(encoding="utf-8"))
    feature_columns = feature_payload.get("android_features", [])
    categorical_features = feature_payload.get("categorical_features", [])

    if not feature_columns:
        raise ValueError("android_feature_columns.json does not contain android_features.")

    return model, preprocessor, label_encoder, feature_columns, categorical_features


def decode_prediction(value: Any, label_encoder) -> str:
    if isinstance(value, str):
        return value

    return str(label_encoder.inverse_transform([int(value)])[0])


def predict_sample(sample_name: str, sample: Dict[str, Any], model, preprocessor, label_encoder, feature_columns, categorical_features):
    missing = [feature for feature in feature_columns if feature not in sample]

    if missing:
        raise ValueError(f"{sample_name} sample is missing features: {missing}")

    input_df = pd.DataFrame([{feature: sample[feature] for feature in feature_columns}], columns=feature_columns)

    for col in categorical_features:
        if col in input_df.columns:
            input_df[col] = input_df[col].fillna("missing").astype(str)

    transformed = preprocessor.transform(input_df)

    raw_prediction = model.predict(transformed)[0]
    prediction = decode_prediction(raw_prediction, label_encoder)

    confidence = None
    probabilities = {}

    if hasattr(model, "predict_proba"):
        probability_values = model.predict_proba(transformed)[0]
        for class_value, probability in zip(model.classes_, probability_values):
            label = decode_prediction(class_value, label_encoder)
            probabilities[label] = round(float(probability), 4)

        confidence = probabilities.get(prediction, round(float(np.max(probability_values)), 4))

    print(f"{sample_name} sample prediction: {prediction}")
    print(f"{sample_name} confidence: {confidence}")
    print(f"{sample_name} probabilities: {probabilities}")

    if prediction not in {"Low", "Medium", "High"}:
        raise AssertionError(f"Unexpected prediction label: {prediction}")

    return prediction


def main() -> None:
    model, preprocessor, label_encoder, feature_columns, categorical_features = load_artifacts()

    print("Loaded Android model artifacts.")
    print(f"Feature count: {len(feature_columns)}")

    predict_sample("Low", LOW_SAMPLE, model, preprocessor, label_encoder, feature_columns, categorical_features)
    predict_sample("Medium", MEDIUM_SAMPLE, model, preprocessor, label_encoder, feature_columns, categorical_features)
    predict_sample("High", HIGH_SAMPLE, model, preprocessor, label_encoder, feature_columns, categorical_features)

    print("All Android prediction tests passed.")


if __name__ == "__main__":
    main()