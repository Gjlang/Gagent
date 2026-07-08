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

ANDROID_MODEL_PATH = MODELS_DIR / "android_appium_model.pkl"
ANDROID_PREPROCESSOR_PATH = MODELS_DIR / "android_preprocessing_pipeline.pkl"
ANDROID_LABEL_ENCODER_PATH = MODELS_DIR / "android_label_encoder.pkl"
ANDROID_FEATURE_COLUMNS_PATH = MODELS_DIR / "android_feature_columns.json"
ANDROID_METADATA_PATH = MODELS_DIR / "android_model_metadata.json"


@dataclass
class ModelArtifacts:
    main_model: Any
    baseline_model: Any
    label_encoder: Any
    preprocessing_pipeline: Any
    feature_columns: Dict[str, Any]
    model_metadata: Dict[str, Any]
    baseline_metadata: Optional[Dict[str, Any]]


@dataclass
class AndroidModelArtifacts:
    android_model: Any
    android_preprocessor: Any
    android_label_encoder: Any
    android_feature_columns: Dict[str, Any]
    android_metadata: Dict[str, Any]


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


def validate_required_android_files() -> None:
    required_files = [
        ANDROID_MODEL_PATH,
        ANDROID_PREPROCESSOR_PATH,
        ANDROID_LABEL_ENCODER_PATH,
        ANDROID_FEATURE_COLUMNS_PATH,
        ANDROID_METADATA_PATH,
    ]

    missing_files = [str(path) for path in required_files if not path.exists()]

    if missing_files:
        missing_text = "\n".join(missing_files)
        raise FileNotFoundError(
            "Required Android model files are missing:\n"
            f"{missing_text}"
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


@lru_cache(maxsize=1)
def load_android_model_artifacts() -> AndroidModelArtifacts:
    validate_required_android_files()

    android_model = _load_joblib(ANDROID_MODEL_PATH)
    android_preprocessor = _load_joblib(ANDROID_PREPROCESSOR_PATH)
    android_label_encoder = _load_joblib(ANDROID_LABEL_ENCODER_PATH)
    android_feature_columns = _read_json(ANDROID_FEATURE_COLUMNS_PATH)
    android_metadata = _read_json(ANDROID_METADATA_PATH)

    return AndroidModelArtifacts(
        android_model=android_model,
        android_preprocessor=android_preprocessor,
        android_label_encoder=android_label_encoder,
        android_feature_columns=android_feature_columns,
        android_metadata=android_metadata,
    )


def is_loaded() -> bool:
    try:
        load_model_artifacts()
        return True
    except Exception:
        return False


def is_android_loaded() -> bool:
    try:
        load_android_model_artifacts()
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


def get_android_model() -> Any:
    return load_android_model_artifacts().android_model


def get_android_preprocessor() -> Any:
    return load_android_model_artifacts().android_preprocessor


def get_android_label_encoder() -> Any:
    return load_android_model_artifacts().android_label_encoder


def get_android_feature_columns() -> Dict[str, Any]:
    return load_android_model_artifacts().android_feature_columns


def get_android_metadata() -> Dict[str, Any]:
    return load_android_model_artifacts().android_metadata


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

    info: Dict[str, Any] = {
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

    # Optionally include Android model info if it's available, without
    # breaking get_model_info() for callers when the Android model isn't set up yet.
    if is_android_loaded():
        android_artifacts = load_android_model_artifacts()

        android_labels: List[str] = []
        if hasattr(android_artifacts.android_label_encoder, "classes_"):
            android_labels = [
                str(label) for label in android_artifacts.android_label_encoder.classes_
            ]

        android_features = android_artifacts.android_feature_columns.get(
            "android_features", []
        )

        info["models"]["android_appium_model"] = {
            "loaded": True,
            "type": type(android_artifacts.android_model).__name__,
            "path": str(ANDROID_MODEL_PATH),
            "supports_probability": hasattr(
                android_artifacts.android_model, "predict_proba"
            ),
            "feature_count": len(android_features),
            "features": android_features,
        }
        info["android_available_labels"] = android_labels
        info["android_metadata"] = android_artifacts.android_metadata
    else:
        info["models"]["android_appium_model"] = {"loaded": False}

    return info


def get_android_model_info() -> Dict[str, Any]:
    artifacts = load_android_model_artifacts()

    available_labels: List[str] = []
    if hasattr(artifacts.android_label_encoder, "classes_"):
        available_labels = [
            str(label) for label in artifacts.android_label_encoder.classes_
        ]

    android_features = artifacts.android_feature_columns.get("android_features", [])

    return {
        "model_loaded": True,
        "model": {
            "android_appium_model": {
                "loaded": True,
                "type": type(artifacts.android_model).__name__,
                "path": str(ANDROID_MODEL_PATH),
                "supports_probability": hasattr(
                    artifacts.android_model, "predict_proba"
                ),
                "feature_count": len(android_features),
                "features": android_features,
            }
        },
        "label_encoder_loaded": True,
        "available_labels": available_labels,
        "preprocessor_loaded": True,
        "android_metadata": artifacts.android_metadata,
    }