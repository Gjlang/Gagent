from pathlib import Path
import json

import joblib
import pandas as pd


BASE_DIR = Path(__file__).resolve().parents[1]

MODEL_PATH = BASE_DIR / "models" / "ux_friction_model.pkl"
LABEL_ENCODER_PATH = BASE_DIR / "models" / "label_encoder.pkl"
SCALER_PATH = BASE_DIR / "models" / "scaler.pkl"
FEATURE_COLUMNS_PATH = BASE_DIR / "models" / "feature_columns.json"


def load_artifacts():
    if not MODEL_PATH.exists():
        raise FileNotFoundError(f"Model not found: {MODEL_PATH}")

    if not LABEL_ENCODER_PATH.exists():
        raise FileNotFoundError(f"Label encoder not found: {LABEL_ENCODER_PATH}")

    if not FEATURE_COLUMNS_PATH.exists():
        raise FileNotFoundError(f"Feature columns file not found: {FEATURE_COLUMNS_PATH}")

    model = joblib.load(MODEL_PATH)
    label_encoder = joblib.load(LABEL_ENCODER_PATH)

    scaler = None
    if SCALER_PATH.exists():
        scaler = joblib.load(SCALER_PATH)

    with FEATURE_COLUMNS_PATH.open("r", encoding="utf-8") as file:
        feature_columns = json.load(file)

    return model, label_encoder, scaler, feature_columns


def build_sample_inputs() -> pd.DataFrame:
    samples = [
        {
            "sample_name": "Low friction example",
            "completion_time": 1200,
            "click_count": 2,
            "scroll_count": 0,
            "keyboard_count": 2,
            "retry_count": 0,
            "error_count": 0,
            "failed_clicks": 0,
            "feedback_delay": 200,
            "task_completed": 1,
            "screenshot_count": 1,
            "error_message_clarity": 2,
        },
        {
            "sample_name": "Medium friction example",
            "completion_time": 6500,
            "click_count": 8,
            "scroll_count": 3,
            "keyboard_count": 5,
            "retry_count": 2,
            "error_count": 1,
            "failed_clicks": 1,
            "feedback_delay": 1200,
            "task_completed": 1,
            "screenshot_count": 2,
            "error_message_clarity": 1,
        },
        {
            "sample_name": "High friction example",
            "completion_time": 18000,
            "click_count": 18,
            "scroll_count": 8,
            "keyboard_count": 10,
            "retry_count": 5,
            "error_count": 4,
            "failed_clicks": 5,
            "feedback_delay": 3500,
            "task_completed": 0,
            "screenshot_count": 5,
            "error_message_clarity": 0,
        },
    ]

    return pd.DataFrame(samples)


def main() -> None:
    model, label_encoder, scaler, feature_columns = load_artifacts()

    samples_df = build_sample_inputs()

    X = samples_df[feature_columns].copy()

    if scaler is not None:
        X = scaler.transform(X)

    predictions = model.predict(X)
    predicted_labels = label_encoder.inverse_transform(predictions)

    print("=" * 80)
    print("GAgent Phase 4 Sample Prediction Test")
    print("=" * 80)

    for sample_name, predicted_label in zip(samples_df["sample_name"], predicted_labels):
        print(f"{sample_name}: {predicted_label}")

    if hasattr(model, "predict_proba"):
        probabilities = model.predict_proba(X)

        print("\nPrediction probabilities:")
        for index, sample_name in enumerate(samples_df["sample_name"]):
            print(f"\n{sample_name}")
            for label, probability in zip(label_encoder.classes_, probabilities[index]):
                print(f"  {label}: {probability:.4f}")


if __name__ == "__main__":
    main()