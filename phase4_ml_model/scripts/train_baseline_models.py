from pathlib import Path
import json
import joblib
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
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

DATASET_PATH = ROOT_DIR / "datasets" / "gagent_common_features_export.csv"
MODELS_DIR = ROOT_DIR / "models"
EVAL_DIR = ROOT_DIR / "outputs" / "evaluation"
FIGURES_DIR = ROOT_DIR / "outputs" / "figures"

MODELS_DIR.mkdir(parents=True, exist_ok=True)
EVAL_DIR.mkdir(parents=True, exist_ok=True)
FIGURES_DIR.mkdir(parents=True, exist_ok=True)

TARGET_COLUMN = "friction_level"

BASELINE_FEATURES = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "task_completed",
]


def plot_confusion_matrix(cm, labels, output_path):
    plt.figure(figsize=(7, 6))
    plt.imshow(cm)
    plt.title("Baseline Model Confusion Matrix")
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
    labels = list(label_encoder.classes_)
    if "High" not in labels:
        return 0.0

    report = classification_report(
        y_true,
        y_pred,
        target_names=label_encoder.classes_,
        output_dict=True,
        zero_division=0,
    )

    return report.get("High", {}).get("recall", 0.0)


def main():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Baseline dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    missing_features = [col for col in BASELINE_FEATURES if col not in df.columns]
    if missing_features:
        raise ValueError(f"Missing baseline features: {missing_features}")

    if TARGET_COLUMN not in df.columns:
        raise ValueError(f"Target column missing: {TARGET_COLUMN}")

    X = df[BASELINE_FEATURES].copy()
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

    models = {
        "Logistic Regression": LogisticRegression(
            max_iter=1000,
            class_weight="balanced",
            random_state=42,
        ),
        "Decision Tree": DecisionTreeClassifier(
            class_weight="balanced",
            random_state=42,
            max_depth=8,
        ),
        "Random Forest": RandomForestClassifier(
            n_estimators=200,
            class_weight="balanced",
            random_state=42,
            n_jobs=-1,
        ),
    }

    results = []
    fitted_models = {}

    for name, model in models.items():
        print(f"Training baseline model: {name}")

        model.fit(X_train, y_train)
        y_pred = model.predict(X_test)

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

        fitted_models[name] = model

        safe_name = name.lower().replace(" ", "_")
        joblib.dump(model, MODELS_DIR / f"baseline_{safe_name}.pkl")

    results_df = pd.DataFrame(results)

    results_df = results_df.sort_values(
        by=["f1_macro", "high_recall", "f1_weighted", "accuracy"],
        ascending=False,
    )

    comparison_path = EVAL_DIR / "baseline_model_comparison.csv"
    results_df.to_csv(comparison_path, index=False)

    best_model_name = results_df.iloc[0]["model_name"]
    best_model = fitted_models[best_model_name]

    joblib.dump(best_model, MODELS_DIR / "baseline_model.pkl")
    joblib.dump(label_encoder, MODELS_DIR / "label_encoder.pkl")

    y_pred_best = best_model.predict(X_test)

    report_text = classification_report(
        y_test,
        y_pred_best,
        target_names=label_encoder.classes_,
        zero_division=0,
    )

    report_path = EVAL_DIR / "baseline_classification_report.txt"
    report_path.write_text(report_text, encoding="utf-8")

    cm = confusion_matrix(y_test, y_pred_best)
    plot_confusion_matrix(
        cm,
        label_encoder.classes_,
        FIGURES_DIR / "confusion_matrix_baseline.png",
    )

    metadata = {
        "baseline_features": BASELINE_FEATURES,
        "target_column": TARGET_COLUMN,
        "best_baseline_model": best_model_name,
        "class_labels": list(label_encoder.classes_),
        "dataset_path": str(DATASET_PATH),
        "row_count": int(len(df)),
    }

    metadata_path = MODELS_DIR / "baseline_metadata.json"
    metadata_path.write_text(json.dumps(metadata, indent=4), encoding="utf-8")

    print("Baseline training completed.")
    print(f"Best baseline model: {best_model_name}")
    print(f"Model comparison saved to: {comparison_path}")
    print(f"Classification report saved to: {report_path}")
    print(f"Best baseline model saved to: {MODELS_DIR / 'baseline_model.pkl'}")


if __name__ == "__main__":
    main()