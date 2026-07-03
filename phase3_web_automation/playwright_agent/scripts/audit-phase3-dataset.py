import argparse
import json
from pathlib import Path

import pandas as pd

ROOT = Path(__file__).resolve().parents[1]
OUTPUT_ROOT = ROOT / "outputs"

EXPECTED_COLUMNS = [
    "flow_type",
    "scenario_type",
    "viewport_type",
    "network_condition",
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
    "friction_score",
    "friction_level",
]

NUMERIC_COLUMNS = [
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
    "friction_score",
]


def dataset_paths(mode: str):
    if mode == "final":
        folder = OUTPUT_ROOT / "final"
        dataset = folder / "gagent_full_ux_friction_dataset.csv"
        report = folder / "final_dataset_audit_report.md"
    else:
        folder = OUTPUT_ROOT / "pilot"
        dataset = folder / "gagent_phase3_pilot_dataset.csv"
        report = folder / "pilot_dataset_audit_report.md"
    return folder, dataset, report


def markdown_table(df: pd.DataFrame) -> str:
    if df.empty:
        return "No rows."
    columns = list(df.columns)
    lines = []
    lines.append("| " + " | ".join(columns) + " |")
    lines.append("| " + " | ".join(["---"] * len(columns)) + " |")
    for _, row in df.iterrows():
        lines.append("| " + " | ".join(str(row[col]) for col in columns) + " |")
    return "\n".join(lines)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--mode", choices=["pilot", "final"], default="pilot")
    args = parser.parse_args()

    folder, dataset_path, report_path = dataset_paths(args.mode)
    if not dataset_path.exists():
        raise FileNotFoundError(f"Dataset not found: {dataset_path}")

    df = pd.read_csv(dataset_path)
    folder.mkdir(parents=True, exist_ok=True)

    missing_columns = [column for column in EXPECTED_COLUMNS if column not in df.columns]
    extra_columns = [column for column in df.columns if column not in EXPECTED_COLUMNS]
    missing_values = df.isna().sum().reset_index()
    missing_values.columns = ["column", "missing_count"]
    duplicate_count = int(df.duplicated().sum())

    class_dist = df["friction_level"].value_counts().rename_axis("friction_level").reset_index(name="row_count")
    flow_dist = df["flow_type"].value_counts().rename_axis("flow_type").reset_index(name="row_count")
    scenario_dist = df["scenario_type"].value_counts().rename_axis("scenario_type").reset_index(name="row_count")
    viewport_dist = df["viewport_type"].value_counts().rename_axis("viewport_type").reset_index(name="row_count")

    class_dist.to_csv(folder / "class_distribution.csv", index=False)
    flow_dist.to_csv(folder / "flow_distribution.csv", index=False)
    scenario_dist.to_csv(folder / "scenario_distribution.csv", index=False)
    viewport_dist.to_csv(folder / "viewport_distribution.csv", index=False)

    numeric_summary = df[[column for column in NUMERIC_COLUMNS if column in df.columns]].describe().T.reset_index()
    numeric_summary = numeric_summary.rename(columns={"index": "attribute"})

    issues = []
    if missing_columns:
        issues.append(f"Missing schema columns: {', '.join(missing_columns)}")
    if extra_columns:
        issues.append(f"Extra columns found: {', '.join(extra_columns)}")
    if int(missing_values["missing_count"].sum()) > 0:
        issues.append("Dataset contains missing values.")
    if duplicate_count > 0:
        issues.append(f"Dataset contains {duplicate_count} duplicate rows.")
    if df["flow_type"].astype(str).str.contains("http|/", regex=True).any():
        issues.append("flow_type contains URL-like values. This must be fixed.")
    if "source_dataset" in df.columns or "screenshot_count" in df.columns or "page_url" in df.columns:
        issues.append("Forbidden old feature columns are still present.")
    if df["task_completed"].nunique() == 1:
        issues.append("task_completed has only one value; task failure scenarios are not being captured.")
    if df["popup_detected"].sum() == 0:
        issues.append("popup_detected is always 0; popup scenarios are not being captured.")
    if df["cumulative_layout_shift"].max() == 0:
        issues.append("cumulative_layout_shift is always 0; layout shift scenarios may not be captured.")
    if df["feedback_delay_ms"].max() == 0:
        issues.append("feedback_delay_ms is always 0; feedback timing is not captured.")

    report = []
    report.append(f"# GAgent Phase 3 {args.mode.title()} Dataset Audit Report")
    report.append("")
    report.append("## 1. Basic Summary")
    report.append(f"- Dataset path: `{dataset_path}`")
    report.append(f"- Total rows: {len(df)}")
    report.append(f"- Total columns: {len(df.columns)}")
    report.append(f"- Duplicate rows: {duplicate_count}")
    report.append(f"- Missing values: {int(missing_values['missing_count'].sum())}")
    report.append("")
    report.append("## 2. Schema Check")
    report.append(f"- Missing columns: {missing_columns if missing_columns else 'None'}")
    report.append(f"- Extra columns: {extra_columns if extra_columns else 'None'}")
    report.append("")
    report.append("## 3. Class Distribution")
    report.append(markdown_table(class_dist))
    report.append("")
    report.append("## 4. Flow Distribution")
    report.append(markdown_table(flow_dist))
    report.append("")
    report.append("## 5. Scenario Distribution")
    report.append(markdown_table(scenario_dist))
    report.append("")
    report.append("## 6. Viewport Distribution")
    report.append(markdown_table(viewport_dist))
    report.append("")
    report.append("## 7. Attribute Ranges")
    report.append(markdown_table(numeric_summary[["attribute", "min", "mean", "max"]].round(3)))
    report.append("")
    report.append("## 8. Issues")
    if issues:
        for issue in issues:
            report.append(f"- {issue}")
    else:
        report.append("- No critical issues found.")
    report.append("")
    report.append("## 9. Readiness Decision")
    if issues:
        report.append("Not ready. Fix the issues above and regenerate the dataset.")
    else:
        report.append("Ready for the next Phase 3 step. If this is the final audit, the dataset is ready for Phase 4 model training.")

    report_path.write_text("\n".join(report), encoding="utf-8")

    summary_json = {
        "mode": args.mode,
        "rows": int(len(df)),
        "columns": int(len(df.columns)),
        "duplicate_rows": duplicate_count,
        "missing_values": int(missing_values["missing_count"].sum()),
        "issues": issues,
    }
    (folder / "audit_summary.json").write_text(json.dumps(summary_json, indent=2), encoding="utf-8")
    print(json.dumps(summary_json, indent=2))
    print(f"Audit report saved to: {report_path}")


if __name__ == "__main__":
    main()
