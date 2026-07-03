from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from typing import Any, List, Optional
import json

import joblib


BASE_DIR = Path(__file__).resolve().parents[1]
MODELS_DIR = BASE_DIR / "models"

MODEL_PATH = MODELS_DIR / "ux_friction_model.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "label_encoder.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "feature_columns.json"
SCALER_PATH = MODELS_DIR / "scaler.pkl"


@dataclass
class ModelArtifacts:
    model: Any
    label_encoder: Any
    feature_columns: List[str]
    scaler: Optional[Any]
    model_path: Path
    label_encoder_path: Path
    feature_columns_path: Path
    scaler_path: Optional[Path]


def validate_required_files() -> None:
    required_files = [
        MODEL_PATH,
        LABEL_ENCODER_PATH,
        FEATURE_COLUMNS_PATH,
    ]

    missing_files = [str(file_path) for file_path in required_files if not file_path.exists()]

    if missing_files:
        missing_text = "\n".join(missing_files)
        raise FileNotFoundError(
            "Required model artifact files are missing:\n"
            f"{missing_text}\n\n"
            "Copy the files from phase4_ml_model/models into ai-service/models."
        )


@lru_cache(maxsize=1)
def load_model_artifacts() -> ModelArtifacts:
    validate_required_files()

    try:
        model = joblib.load(MODEL_PATH)
    except Exception as error:
        raise RuntimeError(f"Failed to load model file: {MODEL_PATH}. Error: {error}") from error

    try:
        label_encoder = joblib.load(LABEL_ENCODER_PATH)
    except Exception as error:
        raise RuntimeError(
            f"Failed to load label encoder file: {LABEL_ENCODER_PATH}. Error: {error}"
        ) from error

    try:
        with FEATURE_COLUMNS_PATH.open("r", encoding="utf-8") as file:
            feature_columns = json.load(file)
    except Exception as error:
        raise RuntimeError(
            f"Failed to load feature columns file: {FEATURE_COLUMNS_PATH}. Error: {error}"
        ) from error

    if not isinstance(feature_columns, list) or not feature_columns:
        raise ValueError("feature_columns.json must contain a non-empty JSON list.")

    scaler = None
    scaler_path = None

    if SCALER_PATH.exists():
        try:
            scaler = joblib.load(SCALER_PATH)
            scaler_path = SCALER_PATH
        except Exception as error:
            raise RuntimeError(f"Failed to load scaler file: {SCALER_PATH}. Error: {error}") from error

    return ModelArtifacts(
        model=model,
        label_encoder=label_encoder,
        feature_columns=feature_columns,
        scaler=scaler,
        model_path=MODEL_PATH,
        label_encoder_path=LABEL_ENCODER_PATH,
        feature_columns_path=FEATURE_COLUMNS_PATH,
        scaler_path=scaler_path,
    )


def get_model_info() -> dict:
    artifacts = load_model_artifacts()

    available_labels = []
    if hasattr(artifacts.label_encoder, "classes_"):
        available_labels = [str(label) for label in artifacts.label_encoder.classes_]

    return {
        "model_loaded": True,
        "model_type": type(artifacts.model).__name__,
        "model_path": str(artifacts.model_path),
        "label_encoder_loaded": True,
        "feature_columns": artifacts.feature_columns,
        "scaler_loaded": artifacts.scaler is not None,
        "available_labels": available_labels,
        "supports_probability": hasattr(artifacts.model, "predict_proba"),
    }