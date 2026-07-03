from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from typing import Any, Dict, List, Optional
import json

import joblib


BASE_DIR = Path(__file__).resolve().parents[1]
MODELS_DIR = BASE_DIR / "models"

MAIN_MODEL_PATH = MODELS_DIR / "main_gagent_model.pkl"
BASELINE_MODEL_PATH = MODELS_DIR / "baseline_model.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "label_encoder.pkl"
PREPROCESSING_PIPELINE_PATH = MODELS_DIR / "preprocessing_pipeline.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "feature_columns.json"
MODEL_METADATA_PATH = MODELS_DIR / "model_metadata.json"
BASELINE_METADATA_PATH = MODELS_DIR / "baseline_metadata.json"


@dataclass
class ModelArtifacts:
    main_model: Any
    baseline_model: Any
    label_encoder: Any
    preprocessing_pipeline: Any
    feature_columns: Dict[str, Any]
    model_metadata: Dict[str, Any]
    baseline_metadata: Optional[Dict[str, Any]]


def _read_json(path: Path) -> Dict[str, Any]:
    try:
        with path.open("r", encoding="utf-8") as file:
            return json.load(file)
    except Exception as error:
        raise RuntimeError(f"Failed to read JSON file: {path}. Error: {error}") from error


def _load_joblib(path: Path) -> Any:
    try:
        return joblib.load(path)
    except Exception as error:
        raise RuntimeError(f"Failed to load model artifact: {path}. Error: {error}") from error


def validate_required_files() -> None:
    required_files = [
        MAIN_MODEL_PATH,
        BASELINE_MODEL_PATH,
        LABEL_ENCODER_PATH,
        PREPROCESSING_PIPELINE_PATH,
        FEATURE_COLUMNS_PATH,
        MODEL_METADATA_PATH,
    ]

    missing_files = [str(path) for path in required_files if not path.exists()]

    if missing_files:
        missing_text = "\n".join(missing_files)
        raise FileNotFoundError(
            "Required Phase 5 model files are missing:\n"
            f"{missing_text}\n\n"
            "Fix: copy the required files from:\n"
            "D:\\FYP\\GAgent\\GAgent\\phase4_ml_model\\models\n"
            "to:\n"
            "D:\\FYP\\GAgent\\GAgent\\ai-service\\models"
        )


@lru_cache(maxsize=1)
def load_model_artifacts() -> ModelArtifacts:
    validate_required_files()

    main_model = _load_joblib(MAIN_MODEL_PATH)
    baseline_model = _load_joblib(BASELINE_MODEL_PATH)
    label_encoder = _load_joblib(LABEL_ENCODER_PATH)
    preprocessing_pipeline = _load_joblib(PREPROCESSING_PIPELINE_PATH)
    feature_columns = _read_json(FEATURE_COLUMNS_PATH)
    model_metadata = _read_json(MODEL_METADATA_PATH)

    baseline_metadata = None
    if BASELINE_METADATA_PATH.exists():
        baseline_metadata = _read_json(BASELINE_METADATA_PATH)

    if "main_features" not in feature_columns:
        raise ValueError(
            "feature_columns.json is invalid. Expected key: main_features"
        )

    if not isinstance(feature_columns["main_features"], list):
        raise ValueError(
            "feature_columns.json is invalid. main_features must be a list."
        )

    return ModelArtifacts(
        main_model=main_model,
        baseline_model=baseline_model,
        label_encoder=label_encoder,
        preprocessing_pipeline=preprocessing_pipeline,
        feature_columns=feature_columns,
        model_metadata=model_metadata,
        baseline_metadata=baseline_metadata,
    )


def is_loaded() -> bool:
    try:
        load_model_artifacts()
        return True
    except Exception:
        return False


def get_main_model() -> Any:
    return load_model_artifacts().main_model


def get_baseline_model() -> Any:
    return load_model_artifacts().baseline_model


def get_label_encoder() -> Any:
    return load_model_artifacts().label_encoder


def get_preprocessing_pipeline() -> Any:
    return load_model_artifacts().preprocessing_pipeline


def get_feature_columns() -> Dict[str, Any]:
    return load_model_artifacts().feature_columns


def get_model_metadata() -> Dict[str, Any]:
    return load_model_artifacts().model_metadata


def get_model_info() -> Dict[str, Any]:
    artifacts = load_model_artifacts()

    available_labels: List[str] = []
    if hasattr(artifacts.label_encoder, "classes_"):
        available_labels = [str(label) for label in artifacts.label_encoder.classes_]

    main_features = artifacts.feature_columns.get("main_features", [])

    baseline_features = []
    if artifacts.baseline_metadata:
        baseline_features = artifacts.baseline_metadata.get("baseline_features", [])

    if not baseline_features and hasattr(artifacts.baseline_model, "feature_names_in_"):
        baseline_features = [
            str(feature) for feature in artifacts.baseline_model.feature_names_in_
        ]

    return {
        "model_loaded": True,
        "models": {
            "main_gagent_model": {
                "loaded": True,
                "type": type(artifacts.main_model).__name__,
                "path": str(MAIN_MODEL_PATH),
                "supports_probability": hasattr(artifacts.main_model, "predict_proba"),
                "feature_count": len(main_features),
                "features": main_features,
            },
            "baseline_model": {
                "loaded": True,
                "type": type(artifacts.baseline_model).__name__,
                "path": str(BASELINE_MODEL_PATH),
                "supports_probability": hasattr(artifacts.baseline_model, "predict_proba"),
                "feature_count": len(baseline_features),
                "features": baseline_features,
            },
        },
        "label_encoder_loaded": True,
        "available_labels": available_labels,
        "preprocessing_pipeline_loaded": True,
        "model_metadata": artifacts.model_metadata,
        "baseline_metadata": artifacts.baseline_metadata,
    }