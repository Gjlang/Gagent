from __future__ import annotations

import json
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Tuple
from sklearn.pipeline import Pipeline
from sklearn.impute import SimpleImputer

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
)
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, OneHotEncoder, StandardScaler
from sklearn.tree import DecisionTreeClassifier
from sklearn.linear_model import LogisticRegression


ROOT_DIR = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT_DIR / "datasets" / "android_appium_dataset_10005_rows_labeled.csv"

MODELS_DIR = ROOT_DIR / "models"
EVALUATION_DIR = ROOT_DIR / "outputs" / "evaluation"

MODEL_PATH = MODELS_DIR / "android_appium_model.pkl"
PREPROCESSOR_PATH = MODELS_DIR / "android_preprocessing_pipeline.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "android_label_encoder.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "android_feature_columns.json"
METADATA_PATH = MODELS_DIR / "android_model_metadata.json"

MODEL_COMPARISON_PATH = EVALUATION_DIR / "android_model_comparison.csv"
CLASSIFICATION_REPORT_PATH = EVALUATION_DIR / "android_classification_report.txt"
CONFUSION_MATRIX_PATH = EVALUATION_DIR / "android_confusion_matrix.csv"

TARGET_COLUMN = "friction_level"

RECOMMENDED_FEATURES = [
    "flow_type",
    "device_type",
    "platform_name",
    "task_completed",
    "task_failed",
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "unnecessary_clicks",
    "path_deviation_score",
    "app_launch_time_ms",
    "screen_load_time_ms",
    "feedback_delay_ms",
    "interaction_response_time_ms",
    "finish_time_ms",
    "error_message_present",
    "error_message_clarity",
    "popup_detected",
    "overlay_blocks_action",
    "timeout_occurred",
    "crash_detected",
    "anr_detected",
]

EXCLUDE_COLUMNS = [
    "friction_level",
    "friction_level_prediction",
    "scenario_type",
    "run_index",
    "target_type",
    "id",
    "row_id",
    "timestamp",
    "created_at",
    "updated_at",
]

RANDOM_STATE = 42
TEST_SIZE = 0.20


def make_one_hot_encoder() -> OneHotEncoder:
    try:
        return OneHotEncoder(handle_unknown="ignore", sparse_output=False)
    except TypeError:
        return OneHotEncoder(handle_unknown="ignore", sparse=False)


def validate_dataset(df: pd.DataFrame) -> None:
    if TARGET_COLUMN not in df.columns:
        raise ValueError(f"Missing target column: {TARGET_COLUMN}")

    if df[TARGET_COLUMN].isna().any():
        raise ValueError("Target column contains missing values.")

    expected_labels = {"Low", "Medium", "High"}
    actual_labels = set(df[TARGET_COLUMN].astype(str).unique())

    missing_labels = expected_labels - actual_labels
    unexpected_labels = actual_labels - expected_labels

    if missing_labels:
        raise ValueError(f"Missing expected labels: {sorted(missing_labels)}")

    if unexpected_labels:
        raise ValueError(f"Unexpected labels: {sorted(unexpected_labels)}")


def select_features(df: pd.DataFrame) -> Tuple[List[str], List[str], List[str], List[str]]:
    existing_features = [feature for feature in RECOMMENDED_FEATURES if feature in df.columns]

    missing_recommended = [
        feature for feature in RECOMMENDED_FEATURES
        if feature not in df.columns
    ]

    constant_features = [
        feature for feature in existing_features
        if df[feature].nunique(dropna=False) <= 1
    ]

    selected_features = [
        feature for feature in existing_features
        if feature not in constant_features
    ]

    if not selected_features:
        raise ValueError("No usable ML features remain after dropping missing and constant columns.")

    numeric_features = df[selected_features].select_dtypes(include="number").columns.tolist()
    categorical_features = [feature for feature in selected_features if feature not in numeric_features]

    return selected_features, numeric_features, categorical_features, missing_recommended, constant_features


def build_preprocessor(numeric_features: List[str], categorical_features: List[str]) -> ColumnTransformer:
    transformers = []

    if numeric_features:
        numeric_pipeline = Pipeline(
            steps=[
                ("imputer", SimpleImputer(strategy="median")),
                ("scaler", StandardScaler()),
            ]
        )

        transformers.append(("numeric", numeric_pipeline, numeric_features))

    if categorical_features:
        categorical_pipeline = Pipeline(
            steps=[
                ("imputer", SimpleImputer(strategy="constant", fill_value="missing")),
                ("onehot", make_one_hot_encoder()),
            ]
        )

        transformers.append(("categorical", categorical_pipeline, categorical_features))

    return ColumnTransformer(transformers=transformers, remainder="drop")

def build_models() -> Dict[str, Any]:
    return {
        "Logistic Regression": LogisticRegression(
            max_iter=2000,
            class_weight="balanced",
            random_state=RANDOM_STATE,
        ),
        "Decision Tree": DecisionTreeClassifier(
            random_state=RANDOM_STATE,
            class_weight="balanced",
            max_depth=8,
            min_samples_leaf=5,
        ),
        "Random Forest": RandomForestClassifier(
            n_estimators=250,
            random_state=RANDOM_STATE,
            class_weight="balanced",
            max_depth=None,
            min_samples_leaf=2,
            n_jobs=-1,
        ),
    }


def evaluate_model(
    model_name: str,
    model: Any,
    x_test_transformed: Any,
    y_test: Any,
    label_encoder: LabelEncoder,
) -> Dict[str, Any]:
    y_pred = model.predict(x_test_transformed)

    report_dict = classification_report(
        y_test,
        y_pred,
        labels=list(range(len(label_encoder.classes_))),
        target_names=label_encoder.classes_,
        output_dict=True,
        zero_division=0,
    )

    return {
        "model_name": model_name,
        "accuracy": accuracy_score(y_test, y_pred),
        "precision_macro": precision_score(y_test, y_pred, average="macro", zero_division=0),
        "recall_macro": recall_score(y_test, y_pred, average="macro", zero_division=0),
        "f1_macro": f1_score(y_test, y_pred, average="macro", zero_division=0),
        "f1_weighted": f1_score(y_test, y_pred, average="weighted", zero_division=0),
        "high_recall": report_dict.get("High", {}).get("recall", 0.0),
        "y_pred": y_pred,
        "classification_report_dict": report_dict,
    }


def main() -> None:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    EVALUATION_DIR.mkdir(parents=True, exist_ok=True)

    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    print("Training Android Appium experimental ML models...")
    print(f"Dataset path: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)
    print(f"Dataset loaded: {df.shape[0]} rows x {df.shape[1]} columns")

    validate_dataset(df)

    selected_features, numeric_features, categorical_features, missing_recommended, constant_features = select_features(df)

    print("\nExcluded leakage/non-input columns:")
    for col in EXCLUDE_COLUMNS:
        if col in df.columns:
            print(f"- {col}")

    print("\nMissing recommended features:")
    for col in missing_recommended:
        print(f"- {col}")

    print("\nDropped constant features:")
    for col in constant_features:
        print(f"- {col}")

    print("\nFinal selected Android ML features:")
    for col in selected_features:
        print(f"- {col}")

    x = df[selected_features].copy()

    for col in categorical_features:
        x[col] = x[col].fillna("missing").astype(str)

    for col in numeric_features:
        x[col] = pd.to_numeric(x[col], errors="coerce")

    y_raw = df[TARGET_COLUMN].astype(str).copy()
    label_encoder = LabelEncoder()
    y = label_encoder.fit_transform(y_raw)

    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=TEST_SIZE,
        random_state=RANDOM_STATE,
        stratify=y,
    )

    preprocessor = build_preprocessor(numeric_features, categorical_features)

    x_train_transformed = preprocessor.fit_transform(x_train)
    x_test_transformed = preprocessor.transform(x_test)

    models = build_models()

    comparison_rows = []
    trained_models = {}
    evaluation_cache = {}

    for model_name, model in models.items():
        print(f"\nTraining: {model_name}")
        model.fit(x_train_transformed, y_train)

        result = evaluate_model(
            model_name=model_name,
            model=model,
            x_test_transformed=x_test_transformed,
            y_test=y_test,
            label_encoder=label_encoder,
        )

        trained_models[model_name] = model
        evaluation_cache[model_name] = result

        comparison_rows.append(
            {
                "model_name": model_name,
                "accuracy": result["accuracy"],
                "precision_macro": result["precision_macro"],
                "recall_macro": result["recall_macro"],
                "f1_macro": result["f1_macro"],
                "f1_weighted": result["f1_weighted"],
                "high_recall": result["high_recall"],
            }
        )

    comparison_df = pd.DataFrame(comparison_rows)
    comparison_df = comparison_df.sort_values(
        by=["f1_macro", "high_recall", "f1_weighted", "accuracy"],
        ascending=False,
    )

    best_model_name = str(comparison_df.iloc[0]["model_name"])
    best_model = trained_models[best_model_name]
    best_result = evaluation_cache[best_model_name]

    print("\nAndroid model comparison:")
    print(comparison_df.to_string(index=False))

    print(f"\nBest Android model selected: {best_model_name}")

    comparison_df.to_csv(MODEL_COMPARISON_PATH, index=False)

    report_text = classification_report(
        y_test,
        best_result["y_pred"],
        labels=list(range(len(label_encoder.classes_))),
        target_names=label_encoder.classes_,
        zero_division=0,
    )

    CLASSIFICATION_REPORT_PATH.write_text(
        f"Best model: {best_model_name}\n\n{report_text}",
        encoding="utf-8",
    )

    matrix = confusion_matrix(
        y_test,
        best_result["y_pred"],
        labels=list(range(len(label_encoder.classes_))),
    )

    confusion_df = pd.DataFrame(
        matrix,
        index=[f"actual_{label}" for label in label_encoder.classes_],
        columns=[f"predicted_{label}" for label in label_encoder.classes_],
    )
    confusion_df.to_csv(CONFUSION_MATRIX_PATH)

    joblib.dump(best_model, MODEL_PATH)
    joblib.dump(preprocessor, PREPROCESSOR_PATH)
    joblib.dump(label_encoder, LABEL_ENCODER_PATH)

    feature_payload = {
        "android_features": selected_features,
        "numeric_features": numeric_features,
        "categorical_features": categorical_features,
        "dropped_constant_features": constant_features,
        "missing_recommended_features": missing_recommended,
        "excluded_columns": [col for col in EXCLUDE_COLUMNS if col in df.columns],
        "target_column": TARGET_COLUMN,
    }

    FEATURE_COLUMNS_PATH.write_text(
        json.dumps(feature_payload, indent=2),
        encoding="utf-8",
    )

    best_metrics = comparison_df.iloc[0].to_dict()

    metadata = {
        "model_type": "android_appium_experimental_extension",
        "best_model_name": best_model_name,
        "dataset_path": str(DATASET_PATH),
        "dataset_shape": {
            "rows": int(df.shape[0]),
            "columns": int(df.shape[1]),
        },
        "label_distribution": df[TARGET_COLUMN].value_counts().to_dict(),
        "label_classes": label_encoder.classes_.tolist(),
        "selected_features": selected_features,
        "numeric_features": numeric_features,
        "categorical_features": categorical_features,
        "excluded_columns": [col for col in EXCLUDE_COLUMNS if col in df.columns],
        "dropped_constant_features": constant_features,
        "selection_priority": [
            "f1_macro",
            "high_recall",
            "f1_weighted",
            "accuracy",
        ],
        "best_model_metrics": {
            key: float(value) if isinstance(value, (int, float)) else value
            for key, value in best_metrics.items()
        },
        "created_at": datetime.now().isoformat(timespec="seconds"),
        "scope_note": (
            "The Web GAgent model remains the main model. "
            "The Android Appium model is an experimental extension trained on controlled Android dummy-app UX-friction data."
        ),
        "limitation": (
            "High scores are expected because the Android dataset is controlled and scenario-generated. "
            "Do not overclaim real-world Android generalization."
        ),
    }

    METADATA_PATH.write_text(
        json.dumps(metadata, indent=2),
        encoding="utf-8",
    )

    print("\nSaved model files:")
    print(f"- {MODEL_PATH}")
    print(f"- {PREPROCESSOR_PATH}")
    print(f"- {LABEL_ENCODER_PATH}")
    print(f"- {FEATURE_COLUMNS_PATH}")
    print(f"- {METADATA_PATH}")

    print("\nSaved evaluation files:")
    print(f"- {MODEL_COMPARISON_PATH}")
    print(f"- {CLASSIFICATION_REPORT_PATH}")
    print(f"- {CONFUSION_MATRIX_PATH}")

    print("\nAndroid model training completed.")

    if float(best_metrics["accuracy"]) >= 0.95:
        print(
            "\nNote: High accuracy is expected because this Android dataset is controlled and scenario-generated. "
            "Report it as experimental controlled performance, not real-world Android generalization."
        )


if __name__ == "__main__":
    main()