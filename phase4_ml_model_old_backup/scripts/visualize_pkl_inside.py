from pathlib import Path
import json
import joblib
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.tree import plot_tree, export_text


BASE_DIR = Path(__file__).resolve().parents[1]

MODELS_DIR = BASE_DIR / "models"
OUTPUT_DIR = BASE_DIR / "outputs" / "pkl_visualization"

FINAL_MODEL_PATH = MODELS_DIR / "ux_friction_model.pkl"
RANDOM_FOREST_PATH = MODELS_DIR / "random_forest_model.pkl"
LOGISTIC_REGRESSION_PATH = MODELS_DIR / "logistic_regression_model.pkl"
DECISION_TREE_PATH = MODELS_DIR / "decision_tree_model.pkl"
LABEL_ENCODER_PATH = MODELS_DIR / "label_encoder.pkl"
SCALER_PATH = MODELS_DIR / "scaler.pkl"
FEATURE_COLUMNS_PATH = MODELS_DIR / "feature_columns.json"


def load_json(path):
    with open(path, "r", encoding="utf-8") as file:
        return json.load(file)


def save_text(path, content):
    with open(path, "w", encoding="utf-8") as file:
        file.write(content)


def plot_feature_importance(model, feature_columns, title, output_path):
    if not hasattr(model, "feature_importances_"):
        print(f"Skipped feature importance for {title}: not available.")
        return

    importance_df = pd.DataFrame({
        "feature": feature_columns,
        "importance": model.feature_importances_
    }).sort_values(by="importance", ascending=True)

    plt.figure(figsize=(10, 7))
    plt.barh(importance_df["feature"], importance_df["importance"])
    plt.title(title)
    plt.xlabel("Importance Score")
    plt.ylabel("Feature")
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()

    importance_csv_path = output_path.with_suffix(".csv")
    importance_df.sort_values(by="importance", ascending=False).to_csv(
        importance_csv_path,
        index=False
    )


def plot_random_forest_single_tree(random_forest_model, feature_columns, class_names):
    if not hasattr(random_forest_model, "estimators_"):
        print("Random Forest estimators not found.")
        return

    first_tree = random_forest_model.estimators_[0]

    plt.figure(figsize=(28, 16))
    plot_tree(
        first_tree,
        feature_names=feature_columns,
        class_names=class_names,
        filled=True,
        rounded=True,
        max_depth=3,
        fontsize=8
    )
    plt.title("Random Forest - Tree 1 Visualization (Limited Depth)")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "random_forest_tree_1_depth3.png", dpi=300)
    plt.close()

    tree_rules = export_text(
        first_tree,
        feature_names=feature_columns,
        max_depth=5
    )

    save_text(
        OUTPUT_DIR / "random_forest_tree_1_rules_depth5.txt",
        tree_rules
    )


def plot_decision_tree(decision_tree_model, feature_columns, class_names):
    plt.figure(figsize=(28, 16))
    plot_tree(
        decision_tree_model,
        feature_names=feature_columns,
        class_names=class_names,
        filled=True,
        rounded=True,
        max_depth=4,
        fontsize=8
    )
    plt.title("Decision Tree Visualization (Limited Depth)")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "decision_tree_depth4.png", dpi=300)
    plt.close()

    full_rules = export_text(
        decision_tree_model,
        feature_names=feature_columns
    )

    save_text(
        OUTPUT_DIR / "decision_tree_full_rules.txt",
        full_rules
    )


def plot_logistic_regression_coefficients(logistic_model, feature_columns, class_names):
    if not hasattr(logistic_model, "coef_"):
        print("Logistic Regression coefficients not found.")
        return

    coef_df = pd.DataFrame(
        logistic_model.coef_,
        columns=feature_columns,
        index=class_names
    )

    coef_df.to_csv(OUTPUT_DIR / "logistic_regression_coefficients.csv")

    plt.figure(figsize=(13, 7))

    x_positions = range(len(feature_columns))
    bar_width = 0.25

    for index, class_name in enumerate(class_names):
        shifted_positions = [x + (index - 1) * bar_width for x in x_positions]
        plt.bar(
            shifted_positions,
            coef_df.loc[class_name],
            width=bar_width,
            label=class_name
        )

    plt.axhline(0)
    plt.title("Logistic Regression Coefficients by Class")
    plt.xlabel("Feature")
    plt.ylabel("Coefficient Value")
    plt.xticks(list(x_positions), feature_columns, rotation=45, ha="right")
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "logistic_regression_coefficients.png", dpi=300)
    plt.close()


def plot_scaler_values(scaler, feature_columns):
    if not hasattr(scaler, "mean_") or not hasattr(scaler, "scale_"):
        print("Scaler mean or scale values not found.")
        return

    scaler_df = pd.DataFrame({
        "feature": feature_columns,
        "mean": scaler.mean_,
        "scale": scaler.scale_
    })

    scaler_df.to_csv(OUTPUT_DIR / "scaler_mean_scale_values.csv", index=False)

    plt.figure(figsize=(11, 6))
    plt.bar(scaler_df["feature"], scaler_df["mean"])
    plt.title("StandardScaler Mean Values")
    plt.xlabel("Feature")
    plt.ylabel("Mean")
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "scaler_mean_values.png", dpi=300)
    plt.close()

    plt.figure(figsize=(11, 6))
    plt.bar(scaler_df["feature"], scaler_df["scale"])
    plt.title("StandardScaler Scale Values")
    plt.xlabel("Feature")
    plt.ylabel("Scale")
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "scaler_scale_values.png", dpi=300)
    plt.close()


def plot_sample_prediction_probability(model, scaler, label_encoder, feature_columns):
    sample_input = {
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
    }

    sample_df = pd.DataFrame([sample_input], columns=feature_columns)
    sample_scaled = scaler.transform(sample_df)

    prediction = model.predict(sample_scaled)
    predicted_label = label_encoder.inverse_transform(prediction)[0]

    if hasattr(model, "predict_proba"):
        probabilities = model.predict_proba(sample_scaled)[0]

        probability_df = pd.DataFrame({
            "class": label_encoder.classes_,
            "probability": probabilities
        })

        probability_df.to_csv(
            OUTPUT_DIR / "sample_prediction_probability.csv",
            index=False
        )

        plt.figure(figsize=(8, 5))
        plt.bar(probability_df["class"], probability_df["probability"])
        plt.title(f"Sample Prediction Probability - Predicted: {predicted_label}")
        plt.xlabel("Friction Class")
        plt.ylabel("Probability")
        plt.ylim(0, 1)
        plt.tight_layout()
        plt.savefig(OUTPUT_DIR / "sample_prediction_probability.png", dpi=300)
        plt.close()


def save_model_summary(final_model, random_forest_model, logistic_model, decision_tree_model, label_encoder, feature_columns):
    summary_lines = []

    summary_lines.append("=" * 80)
    summary_lines.append("GAgent Phase 4 PKL Internal Visualization Summary")
    summary_lines.append("=" * 80)
    summary_lines.append("")

    summary_lines.append("Final Best Model")
    summary_lines.append("-" * 80)
    summary_lines.append(str(final_model))
    summary_lines.append(f"Object type: {type(final_model)}")
    summary_lines.append("")

    summary_lines.append("Random Forest Model")
    summary_lines.append("-" * 80)
    summary_lines.append(str(random_forest_model))
    summary_lines.append(f"Number of trees: {len(random_forest_model.estimators_)}")
    summary_lines.append(f"Classes: {random_forest_model.classes_}")
    summary_lines.append("")

    summary_lines.append("Logistic Regression Model")
    summary_lines.append("-" * 80)
    summary_lines.append(str(logistic_model))
    summary_lines.append(f"Classes: {logistic_model.classes_}")
    summary_lines.append("")

    summary_lines.append("Decision Tree Model")
    summary_lines.append("-" * 80)
    summary_lines.append(str(decision_tree_model))
    summary_lines.append(f"Tree depth: {decision_tree_model.get_depth()}")
    summary_lines.append(f"Number of leaves: {decision_tree_model.get_n_leaves()}")
    summary_lines.append("")

    summary_lines.append("Label Encoder")
    summary_lines.append("-" * 80)
    summary_lines.append(f"Classes: {list(label_encoder.classes_)}")
    summary_lines.append("")

    summary_lines.append("Feature Columns")
    summary_lines.append("-" * 80)
    for index, feature in enumerate(feature_columns, start=1):
        summary_lines.append(f"{index}. {feature}")

    save_text(
        OUTPUT_DIR / "model_internal_summary.txt",
        "\n".join(summary_lines)
    )


def main():
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    final_model = joblib.load(FINAL_MODEL_PATH)
    random_forest_model = joblib.load(RANDOM_FOREST_PATH)
    logistic_model = joblib.load(LOGISTIC_REGRESSION_PATH)
    decision_tree_model = joblib.load(DECISION_TREE_PATH)
    label_encoder = joblib.load(LABEL_ENCODER_PATH)
    scaler = joblib.load(SCALER_PATH)
    feature_columns = load_json(FEATURE_COLUMNS_PATH)

    class_names = list(label_encoder.classes_)

    save_model_summary(
        final_model,
        random_forest_model,
        logistic_model,
        decision_tree_model,
        label_encoder,
        feature_columns
    )

    plot_feature_importance(
        random_forest_model,
        feature_columns,
        "Random Forest Feature Importance",
        OUTPUT_DIR / "random_forest_feature_importance.png"
    )

    plot_feature_importance(
        decision_tree_model,
        feature_columns,
        "Decision Tree Feature Importance",
        OUTPUT_DIR / "decision_tree_feature_importance.png"
    )

    plot_random_forest_single_tree(
        random_forest_model,
        feature_columns,
        class_names
    )

    plot_decision_tree(
        decision_tree_model,
        feature_columns,
        class_names
    )

    plot_logistic_regression_coefficients(
        logistic_model,
        feature_columns,
        class_names
    )

    plot_scaler_values(
        scaler,
        feature_columns
    )

    plot_sample_prediction_probability(
        final_model,
        scaler,
        label_encoder,
        feature_columns
    )

    print("=" * 80)
    print("PKL internal visualization completed.")
    print("=" * 80)
    print(f"Output folder: {OUTPUT_DIR}")
    print("")
    print("Generated visualizations:")
    print("- random_forest_feature_importance.png")
    print("- decision_tree_feature_importance.png")
    print("- random_forest_tree_1_depth3.png")
    print("- decision_tree_depth4.png")
    print("- logistic_regression_coefficients.png")
    print("- scaler_mean_values.png")
    print("- scaler_scale_values.png")
    print("- sample_prediction_probability.png")
    print("")
    print("Generated text/CSV files:")
    print("- model_internal_summary.txt")
    print("- random_forest_feature_importance.csv")
    print("- decision_tree_feature_importance.csv")
    print("- random_forest_tree_1_rules_depth5.txt")
    print("- decision_tree_full_rules.txt")
    print("- logistic_regression_coefficients.csv")
    print("- scaler_mean_scale_values.csv")
    print("- sample_prediction_probability.csv")


if __name__ == "__main__":
    main()