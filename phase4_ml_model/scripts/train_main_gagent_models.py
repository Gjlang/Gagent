from pathlib import Path
import json
from datetime import datetime
import joblib
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.compose import ColumnTransformer
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, OneHotEncoder
from sklearn.pipeline import Pipeline
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    classification_report,
    confusion_matrix,
)
from sklearn.linear_model import LogisticRegression
from sklearn.tree import DecisionTreeClassifier
from sklearn.ensemble import RandomForestClassifier

ROOT_DIR = Path(__file__).resolve().parents[1]

DATASET_PATH = ROOT_DIR / "datasets" / "gagent_full_ux_friction_dataset.csv"
MODELS_DIR = ROOT_DIR / "models"
EVAL_DIR = ROOT_DIR / "outputs" / "evaluation"
FIGURES_DIR = ROOT_DIR / "outputs" / "figures"

MODELS_DIR.mkdir(parents=True, exist_ok=True)
EVAL_DIR.mkdir(parents=True, exist_ok=True)
FIGURES_DIR.mkdir(parents=True, exist_ok=True)

TARGET_COLUMN = "friction_level"

CATEGORICAL_FEATURES = [
    "flow_type",
    "viewport_type",
]

NUMERIC_FEATURES = [
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
    "page_load_time_ms",
    "dom_content_loaded_ms",
    "time_to_first_byte_ms",
    "feedback_delay_ms",
    "interaction_to_next_paint_ms",
    "cumulative_layout_shift",
    "error_message_present",
    "error_message_clarity",
    "popup_detected",
    "cookie_banner_detected",
    "overlay_blocks_cta",
]

MAIN_FEATURES = CATEGORICAL_FEATURES + NUMERIC_FEATURES

EXCLUDED_COLUMNS = [
    "friction_level",
    "friction_score",
    "scenario_type",
    "network_condition",
    "run_id",
    "source_dataset",
    "screenshot_count",
    "page_url",
    "route",
    "expected_friction_level",
    "screenshot_path",
    "log_path",
    "seed",
    "label_mismatch",
]


def build_preprocessor():
    return ColumnTransformer(
        transformers=[
            ("categorical", OneHotEncoder(handle_unknown="ignore"), CATEGORICAL_FEATURES),
            ("numeric", "passthrough", NUMERIC_FEATURES),
        ],
        remainder="drop",
    )


def plot_confusion_matrix(cm, labels, output_path):
    plt.figure(figsize=(7, 6))
    plt.imshow(cm)
    plt.title("Main GAgent Model Confusion Matrix")
    plt.xlabel("Predicted Label")
    plt.ylabel("Actual Label")
    plt.colorbar()

    plt.xticks(range(len(labels)), labels, rotation=45)
    plt.yticks(range(len(labels)), labels)

    for i in range(len(labels)):
        for j in range(len(labels)):
            plt.text(j, i, cm[i, j], ha="center", va="center")

    plt.tight_layout()
    plt.savefig(output_path, dpi=200)
    plt.close()


def get_high_recall(y_true, y_pred, label_encoder):
    report = classification_report(
        y_true,
        y_pred,
        target_names=label_encoder.classes_,
        output_dict=True,
        zero_division=0,
    )

    return report.get("High", {}).get("recall", 0.0)


def save_feature_importance(best_pipeline, label_encoder):
    model = best_pipeline.named_steps["classifier"]
    preprocessor = best_pipeline.named_steps["preprocessor"]

    if not hasattr(model, "feature_importances_"):
        print("Best model does not support feature importance.")
        return

    feature_names = list(
        preprocessor.named_transformers_["categorical"].get_feature_names_out(CATEGORICAL_FEATURES)
    ) + NUMERIC_FEATURES

    importances = model.feature_importances_

    importance_df = pd.DataFrame({
        "feature": feature_names,
        "importance": importances,
    }).sort_values(by="importance", ascending=False)

    importance_path = EVAL_DIR / "feature_importance.csv"
    importance_df.to_csv(importance_path, index=False)

    top_features = importance_df.head(20).sort_values(by="importance", ascending=True)

    plt.figure(figsize=(10, 8))
    plt.barh(top_features["feature"], top_features["importance"])
    plt.title("Top 20 Feature Importance")
    plt.xlabel("Importance")
    plt.ylabel("Feature")
    plt.tight_layout()
    plt.savefig(FIGURES_DIR / "feature_importance.png", dpi=200)
    plt.close()

    print(f"Feature importance saved to: {importance_path}")


def main():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Main dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    missing_features = [col for col in MAIN_FEATURES if col not in df.columns]
    if missing_features:
        raise ValueError(f"Missing main features: {missing_features}")

    if TARGET_COLUMN not in df.columns:
        raise ValueError(f"Target column missing: {TARGET_COLUMN}")

    leakage_found = [col for col in ["friction_score", "scenario_type"] if col in MAIN_FEATURES]
    if leakage_found:
        raise ValueError(f"Leakage columns are included as features: {leakage_found}")

    X = df[MAIN_FEATURES].copy()
    y = df[TARGET_COLUMN].copy()

    label_encoder = LabelEncoder()
    y_encoded = label_encoder.fit_transform(y)

    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y_encoded,
        test_size=0.2,
        random_state=42,
        stratify=y_encoded,
    )

    model_candidates = {
        "Logistic Regression": LogisticRegression(
            max_iter=1000,
            class_weight="balanced",
            random_state=42,
        ),
        "Decision Tree": DecisionTreeClassifier(
            class_weight="balanced",
            random_state=42,
            max_depth=10,
        ),
        "Random Forest": RandomForestClassifier(
            n_estimators=300,
            class_weight="balanced",
            random_state=42,
            n_jobs=-1,
        ),
    }

    results = []
    fitted_pipelines = {}

    for name, classifier in model_candidates.items():
        print(f"Training main GAgent model: {name}")

        pipeline = Pipeline(
            steps=[
                ("preprocessor", build_preprocessor()),
                ("classifier", classifier),
            ]
        )

        pipeline.fit(X_train, y_train)
        y_pred = pipeline.predict(X_test)

        accuracy = accuracy_score(y_test, y_pred)
        precision_macro = precision_score(y_test, y_pred, average="macro", zero_division=0)
        recall_macro = recall_score(y_test, y_pred, average="macro", zero_division=0)
        f1_macro = f1_score(y_test, y_pred, average="macro", zero_division=0)
        f1_weighted = f1_score(y_test, y_pred, average="weighted", zero_division=0)
        high_recall = get_high_recall(y_test, y_pred, label_encoder)

        results.append({
            "model_name": name,
            "accuracy": accuracy,
            "precision_macro": precision_macro,
            "recall_macro": recall_macro,
            "f1_macro": f1_macro,
            "f1_weighted": f1_weighted,
            "high_recall": high_recall,
        })

        fitted_pipelines[name] = pipeline

        safe_name = name.lower().replace(" ", "_")
        joblib.dump(pipeline, MODELS_DIR / f"main_{safe_name}.pkl")

    results_df = pd.DataFrame(results)

    results_df = results_df.sort_values(
        by=["f1_macro", "high_recall", "f1_weighted", "accuracy"],
        ascending=False,
    )

    comparison_path = EVAL_DIR / "main_gagent_model_comparison.csv"
    results_df.to_csv(comparison_path, index=False)

    best_model_name = results_df.iloc[0]["model_name"]
    best_pipeline = fitted_pipelines[best_model_name]

    joblib.dump(best_pipeline, MODELS_DIR / "main_gagent_model.pkl")
    joblib.dump(best_pipeline.named_steps["preprocessor"], MODELS_DIR / "preprocessing_pipeline.pkl")
    joblib.dump(label_encoder, MODELS_DIR / "label_encoder.pkl")

    y_pred_best = best_pipeline.predict(X_test)

    report_text = classification_report(
        y_test,
        y_pred_best,
        target_names=label_encoder.classes_,
        zero_division=0,
    )

    report_path = EVAL_DIR / "main_gagent_classification_report.txt"
    report_path.write_text(report_text, encoding="utf-8")

    cm = confusion_matrix(y_test, y_pred_best)
    plot_confusion_matrix(
        cm,
        label_encoder.classes_,
        FIGURES_DIR / "confusion_matrix_main_gagent.png",
    )

    save_feature_importance(best_pipeline, label_encoder)

    best_row = results_df.iloc[0].to_dict()

    feature_columns = {
        "main_features": MAIN_FEATURES,
        "categorical_features": CATEGORICAL_FEATURES,
        "numeric_features": NUMERIC_FEATURES,
        "excluded_columns": EXCLUDED_COLUMNS,
        "target_column": TARGET_COLUMN,
    }

    feature_columns_path = MODELS_DIR / "feature_columns.json"
    feature_columns_path.write_text(json.dumps(feature_columns, indent=4), encoding="utf-8")

    metadata = {
        "model_name": "main_gagent_model",
        "model_type": best_model_name,
        "training_dataset_path": str(DATASET_PATH),
        "row_count": int(len(df)),
        "feature_count": int(len(MAIN_FEATURES)),
        "target_label": TARGET_COLUMN,
        "class_labels": list(label_encoder.classes_),
        "accuracy": float(best_row["accuracy"]),
        "macro_f1": float(best_row["f1_macro"]),
        "weighted_f1": float(best_row["f1_weighted"]),
        "high_recall": float(best_row["high_recall"]),
        "created_at": datetime.now().isoformat(timespec="seconds"),
        "phase": "Phase 4 - ML Model Training",
        "important_note": "High performance should be interpreted carefully because the dataset is controlled and rule-generated.",
    }

    metadata_path = MODELS_DIR / "model_metadata.json"
    metadata_path.write_text(json.dumps(metadata, indent=4), encoding="utf-8")

    print("Main GAgent training completed.")
    print(f"Best main model: {best_model_name}")
    print(f"Model comparison saved to: {comparison_path}")
    print(f"Classification report saved to: {report_path}")
    print(f"Best main model saved to: {MODELS_DIR / 'main_gagent_model.pkl'}")
    print(f"Feature columns saved to: {feature_columns_path}")
    print(f"Model metadata saved to: {metadata_path}")


if __name__ == "__main__":
    main()