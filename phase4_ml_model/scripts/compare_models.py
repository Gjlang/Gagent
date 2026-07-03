from pathlib import Path
import pandas as pd

ROOT_DIR = Path(__file__).resolve().parents[1]

EVAL_DIR = ROOT_DIR / "outputs" / "evaluation"
REPORTS_DIR = ROOT_DIR / "outputs" / "reports"

BASELINE_COMPARISON_PATH = EVAL_DIR / "baseline_model_comparison.csv"
MAIN_COMPARISON_PATH = EVAL_DIR / "main_gagent_model_comparison.csv"

FINAL_COMPARISON_PATH = EVAL_DIR / "final_model_comparison.csv"
ANALYSIS_REPORT_PATH = REPORTS_DIR / "baseline_vs_main_analysis.md"

REPORTS_DIR.mkdir(parents=True, exist_ok=True)


def main():
    if not BASELINE_COMPARISON_PATH.exists():
        raise FileNotFoundError(f"Baseline comparison not found: {BASELINE_COMPARISON_PATH}")

    if not MAIN_COMPARISON_PATH.exists():
        raise FileNotFoundError(f"Main comparison not found: {MAIN_COMPARISON_PATH}")

    baseline_df = pd.read_csv(BASELINE_COMPARISON_PATH)
    main_df = pd.read_csv(MAIN_COMPARISON_PATH)

    best_baseline = baseline_df.iloc[0].copy()
    best_main = main_df.iloc[0].copy()

    final_df = pd.DataFrame([
        {
            "model_group": "Baseline/Common Model",
            "best_model": best_baseline["model_name"],
            "feature_set": "Common interaction metrics only",
            "dataset_used": "gagent_common_features_export.csv",
            "accuracy": best_baseline["accuracy"],
            "precision_macro": best_baseline["precision_macro"],
            "recall_macro": best_baseline["recall_macro"],
            "f1_macro": best_baseline["f1_macro"],
            "f1_weighted": best_baseline["f1_weighted"],
            "high_recall": best_baseline["high_recall"],
        },
        {
            "model_group": "Main GAgent Model",
            "best_model": best_main["model_name"],
            "feature_set": "Full GAgent UX friction attributes",
            "dataset_used": "gagent_full_ux_friction_dataset.csv",
            "accuracy": best_main["accuracy"],
            "precision_macro": best_main["precision_macro"],
            "recall_macro": best_main["recall_macro"],
            "f1_macro": best_main["f1_macro"],
            "f1_weighted": best_main["f1_weighted"],
            "high_recall": best_main["high_recall"],
        },
    ])

    final_df.to_csv(FINAL_COMPARISON_PATH, index=False)

    improved = best_main["f1_macro"] >= best_baseline["f1_macro"]

    report_lines = [
        "# Baseline vs Main GAgent Model Analysis",
        "",
        "## Purpose",
        "",
        "This comparison evaluates whether the full GAgent UX friction features improve classification performance compared with a simpler baseline model using common interaction metrics only.",
        "",
        "## Best Baseline Model",
        "",
        f"- Model: {best_baseline['model_name']}",
        f"- Accuracy: {best_baseline['accuracy']:.4f}",
        f"- Macro F1: {best_baseline['f1_macro']:.4f}",
        f"- High Recall: {best_baseline['high_recall']:.4f}",
        "",
        "## Best Main GAgent Model",
        "",
        f"- Model: {best_main['model_name']}",
        f"- Accuracy: {best_main['accuracy']:.4f}",
        f"- Macro F1: {best_main['f1_macro']:.4f}",
        f"- High Recall: {best_main['high_recall']:.4f}",
        "",
        "## Interpretation",
        "",
    ]

    if improved:
        report_lines.append("The main GAgent model achieved equal or better macro F1 performance than the baseline model.")
        report_lines.append("This suggests that the additional UX friction attributes improve or preserve classification quality.")
    else:
        report_lines.append("The main GAgent model did not outperform the baseline by macro F1.")
        report_lines.append("This should be investigated by checking feature leakage removal, feature quality, and model complexity.")

    report_lines.extend([
        "",
        "The baseline model is useful for academic comparison because it uses only common attributes such as completion time, click count, retry count, error count, failed clicks, and task completion.",
        "",
        "The main GAgent model is more suitable for the actual system because it uses richer UX-specific signals such as page load time, feedback delay, layout shift, popup detection, overlay blocking, and path deviation.",
        "",
        "## Important Limitation",
        "",
        "If the model performance is extremely high, it should not be overclaimed. The Phase 3 dataset is controlled and rule-generated, so the model may be learning structured friction patterns from the controlled website. Real-world generalization should be tested later using external websites and Android applications.",
        "",
        "## Final Decision",
        "",
        "The main GAgent model should be used for Phase 5 FastAPI integration because it better represents the actual UX friction detection system.",
    ])

    ANALYSIS_REPORT_PATH.write_text("\n".join(report_lines), encoding="utf-8")

    print("Model comparison completed.")
    print(f"Final comparison saved to: {FINAL_COMPARISON_PATH}")
    print(f"Analysis report saved to: {ANALYSIS_REPORT_PATH}")


if __name__ == "__main__":
    main()