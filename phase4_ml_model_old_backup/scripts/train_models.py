from pathlib import Path
import json
import warnings

import joblib
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.tree import DecisionTreeClassifier
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    classification_report,
    confusion_matrix,
)


warnings.filterwarnings("ignore")


BASE_DIR = Path(__file__).resolve().parents[1]

DATASET_PATH = BASE_DIR / "datasets" / "combined_ux_friction_dataset_v2_full_unique.csv"

MODELS_DIR = BASE_DIR / "models"
FIGURES_DIR = BASE_DIR / "outputs" / "figures"
EVALUATION_DIR = BASE_DIR / "outputs" / "evaluation"
REPORTS_DIR = BASE_DIR / "outputs" / "reports"

MODEL_COMPARISON_PATH = EVALUATION_DIR / "model_comparison.csv"

BEST_MODEL_PATH = MODELS_DIR / "ux_friction_model.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "label_encoder.pkl"
SCALER_PATH = MODELS_DIR / "scaler.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "feature_columns.json"

FEATURE_COLUMNS = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "feedback_delay",
    "task_completed",
    "screenshot_count",
    "error_message_clarity",
]

TARGET_COLUMN = "friction_level"

LABEL_ORDER = ["Low", "Medium", "High"]

UNKNOWN_ALLOWED_COLUMNS = {
    "scroll_count",
    "keyboard_count",
    "feedback_delay",
    "task_completed",
    "error_message_clarity",
}


def prepare_directories() -> None:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)
    FIGURES_DIR.mkdir(parents=True, exist_ok=True)
    EVALUATION_DIR.mkdir(parents=True, exist_ok=True)
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)


def load_dataset() -> pd.DataFrame:
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)
    return df


def clean_dataset(df: pd.DataFrame) -> pd.DataFrame:
    missing_features = [col for col in FEATURE_COLUMNS if col not in df.columns]
    if missing_features:
        raise ValueError(f"Missing feature columns: {missing_features}")

    if TARGET_COLUMN not in df.columns:
        raise ValueError(f"Missing target column: {TARGET_COLUMN}")

    df = df.copy()

    df[TARGET_COLUMN] = df[TARGET_COLUMN].astype(str).str.strip().str.title()
    df = df[df[TARGET_COLUMN].isin(LABEL_ORDER)].copy()

    for col in FEATURE_COLUMNS:
        df[col] = pd.to_numeric(df[col], errors="coerce")
        df[col] = df[col].replace([np.inf, -np.inf], np.nan)

        if col in UNKNOWN_ALLOWED_COLUMNS:
            df.loc[df[col] < -1, col] = np.nan
        else:
            df.loc[df[col] < 0, col] = np.nan

    for col in FEATURE_COLUMNS:
        median_value = df[col].median()
        if pd.isna(median_value):
            median_value = 0
        df[col] = df[col].fillna(median_value)

    return df


def save_confusion_matrix_image(cm: np.ndarray, labels: list, title: str, output_path: Path) -> None:
    plt.figure(figsize=(8, 6))
    plt.imshow(cm, interpolation="nearest")
    plt.title(title)
    plt.colorbar()

    tick_marks = np.arange(len(labels))
    plt.xticks(tick_marks, labels, rotation=45)
    plt.yticks(tick_marks, labels)

    threshold = cm.max() / 2 if cm.max() > 0 else 0

    for i in range(cm.shape[0]):
        for j in range(cm.shape[1]):
            plt.text(
                j,
                i,
                format(cm[i, j], "d"),
                ha="center",
                va="center",
            )

    plt.ylabel("Actual Label")
    plt.xlabel("Predicted Label")
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


def evaluate_model(model_name: str, model, X_test, y_test, label_encoder: LabelEncoder) -> dict:
    y_pred = model.predict(X_test)

    labels_encoded = label_encoder.transform(LABEL_ORDER)

    accuracy = accuracy_score(y_test, y_pred)

    macro_precision = precision_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="macro",
        zero_division=0,
    )

    macro_recall = recall_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="macro",
        zero_division=0,
    )

    macro_f1 = f1_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="macro",
        zero_division=0,
    )

    weighted_precision = precision_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="weighted",
        zero_division=0,
    )

    weighted_recall = recall_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="weighted",
        zero_division=0,
    )

    weighted_f1 = f1_score(
        y_test,
        y_pred,
        labels=labels_encoded,
        average="weighted",
        zero_division=0,
    )

    report = classification_report(
        y_test,
        y_pred,
        labels=labels_encoded,
        target_names=LABEL_ORDER,
        zero_division=0,
    )

    report_path = REPORTS_DIR / f"{model_name.lower().replace(' ', '_')}_classification_report.txt"
    report_path.write_text(report, encoding="utf-8")

    cm = confusion_matrix(y_test, y_pred, labels=labels_encoded)

    cm_path = FIGURES_DIR / f"{model_name.lower().replace(' ', '_')}_confusion_matrix.png"
    save_confusion_matrix_image(
        cm,
        LABEL_ORDER,
        f"{model_name} Confusion Matrix",
        cm_path,
    )

    return {
        "model_name": model_name,
        "accuracy": round(accuracy, 4),
        "macro_precision": round(macro_precision, 4),
        "macro_recall": round(macro_recall, 4),
        "macro_f1": round(macro_f1, 4),
        "weighted_precision": round(weighted_precision, 4),
        "weighted_recall": round(weighted_recall, 4),
        "weighted_f1": round(weighted_f1, 4),
        "classification_report_path": str(report_path),
        "confusion_matrix_path": str(cm_path),
    }


def main() -> None:
    prepare_directories()

    print("=" * 80)
    print("GAgent Phase 4 Model Training Started")
    print("=" * 80)

    df = load_dataset()
    print(f"Loaded dataset: {DATASET_PATH}")
    print(f"Original shape: {df.shape}")

    df = clean_dataset(df)
    print(f"Cleaned shape: {df.shape}")

    X = df[FEATURE_COLUMNS].copy()
    y_text = df[TARGET_COLUMN].copy()

    label_encoder = LabelEncoder()
    label_encoder.classes_ = np.array(LABEL_ORDER)
    y = label_encoder.transform(y_text)

    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.20,
        random_state=42,
        stratify=y,
    )

    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    models = {
        "Random Forest": RandomForestClassifier(
            n_estimators=200,
            random_state=42,
            class_weight="balanced",
            n_jobs=-1,
        ),
        "Logistic Regression": LogisticRegression(
            max_iter=3000,
            random_state=42,
            class_weight="balanced",
        ),
        "Decision Tree": DecisionTreeClassifier(
            random_state=42,
            class_weight="balanced",
        ),
    }

    comparison_results = []
    trained_models = {}

    for model_name, model in models.items():
        print(f"\nTraining model: {model_name}")
        model.fit(X_train_scaled, y_train)
        trained_models[model_name] = model

        model_file_name = model_name.lower().replace(" ", "_") + "_model.pkl"
        model_path = MODELS_DIR / model_file_name
        joblib.dump(model, model_path)

        result = evaluate_model(
            model_name=model_name,
            model=model,
            X_test=X_test_scaled,
            y_test=y_test,
            label_encoder=label_encoder,
        )

        result["saved_model_path"] = str(model_path)
        comparison_results.append(result)

        print(f"{model_name} completed.")
        print(f"Accuracy: {result['accuracy']}")
        print(f"Macro F1-score: {result['macro_f1']}")
        print(f"Weighted F1-score: {result['weighted_f1']}")

    comparison_df = pd.DataFrame(comparison_results)
    comparison_df = comparison_df.sort_values(by="macro_f1", ascending=False)
    comparison_df.to_csv(MODEL_COMPARISON_PATH, index=False)

    best_model_name = comparison_df.iloc[0]["model_name"]
    best_model = trained_models[best_model_name]

    joblib.dump(best_model, BEST_MODEL_PATH)
    joblib.dump(label_encoder, LABEL_ENCODER_PATH)
    joblib.dump(scaler, SCALER_PATH)

    with FEATURE_COLUMNS_PATH.open("w", encoding="utf-8") as file:
        json.dump(FEATURE_COLUMNS, file, indent=4)

    print("\n" + "=" * 80)
    print("GAgent Phase 4 Model Training Completed")
    print("=" * 80)
    print(f"Model comparison saved to: {MODEL_COMPARISON_PATH}")
    print(f"Best model selected by macro F1-score: {best_model_name}")
    print(f"Best model saved to: {BEST_MODEL_PATH}")
    print(f"Label encoder saved to: {LABEL_ENCODER_PATH}")
    print(f"Scaler saved to: {SCALER_PATH}")
    print(f"Feature columns saved to: {FEATURE_COLUMNS_PATH}")
    print("=" * 80)


if __name__ == "__main__":
    main()