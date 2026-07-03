from pathlib import Path
import pandas as pd
import numpy as np

ROOT_DIR = Path(__file__).resolve().parents[1]

FULL_DATASET_PATH = ROOT_DIR / "datasets" / "gagent_full_ux_friction_dataset.csv"
COMMON_DATASET_PATH = ROOT_DIR / "datasets" / "gagent_common_features_export.csv"

AUDIT_DIR = ROOT_DIR / "outputs" / "audit"
AUDIT_DIR.mkdir(parents=True, exist_ok=True)

TARGET_COLUMN = "friction_level"

LEAKAGE_COLUMNS = [
    "friction_level",
    "friction_score",
    "expected_friction_level",
    "scenario_type",
    "run_id",
    "source_dataset",
    "screenshot_count",
    "page_url",
    "route",
    "screenshot_path",
    "log_path",
    "seed",
    "label_mismatch",
]

MAIN_SAFE_FEATURES = [
    "flow_type",
    "viewport_type",
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

BASELINE_SAFE_FEATURES = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "task_completed",
]


def audit_dataset(df: pd.DataFrame, name: str) -> list[str]:
    lines = []

    lines.append(f"# Dataset Audit: {name}")
    lines.append("")
    lines.append(f"Rows: {len(df):,}")
    lines.append(f"Columns: {len(df.columns):,}")
    lines.append(f"Duplicate rows: {df.duplicated().sum():,}")
    lines.append(f"Total missing values: {df.isna().sum().sum():,}")
    lines.append("")

    lines.append("## Column Names")
    lines.append("")
    for col in df.columns:
        lines.append(f"- {col}")
    lines.append("")

    lines.append("## Data Types")
    lines.append("")
    for col, dtype in df.dtypes.items():
        lines.append(f"- {col}: {dtype}")
    lines.append("")

    if TARGET_COLUMN in df.columns:
        class_counts = df[TARGET_COLUMN].value_counts()
        class_counts.to_csv(AUDIT_DIR / f"{name}_class_distribution.csv")

        lines.append("## Class Distribution")
        lines.append("")
        for label, count in class_counts.items():
            percentage = count / len(df) * 100
            lines.append(f"- {label}: {count:,} ({percentage:.2f}%)")
        lines.append("")
    else:
        lines.append("## Class Distribution")
        lines.append("")
        lines.append("Target column not found.")
        lines.append("")

    if "flow_type" in df.columns:
        flow_counts = df["flow_type"].value_counts()
        flow_counts.to_csv(AUDIT_DIR / f"{name}_flow_distribution.csv")

        lines.append("## Flow Distribution")
        lines.append("")
        for label, count in flow_counts.items():
            percentage = count / len(df) * 100
            lines.append(f"- {label}: {count:,} ({percentage:.2f}%)")
        lines.append("")

    if "viewport_type" in df.columns:
        viewport_counts = df["viewport_type"].value_counts()

        lines.append("## Viewport Distribution")
        lines.append("")
        for label, count in viewport_counts.items():
            percentage = count / len(df) * 100
            lines.append(f"- {label}: {count:,} ({percentage:.2f}%)")
        lines.append("")

    if "scenario_type" in df.columns:
        scenario_counts = df["scenario_type"].value_counts()

        lines.append("## Scenario Distribution")
        lines.append("")
        for label, count in scenario_counts.items():
            percentage = count / len(df) * 100
            lines.append(f"- {label}: {count:,} ({percentage:.2f}%)")
        lines.append("")

    numeric_df = df.select_dtypes(include=[np.number])
    if not numeric_df.empty:
        numeric_summary = numeric_df.describe().T
        numeric_summary["missing_values"] = numeric_df.isna().sum()
        numeric_summary["unique_values"] = numeric_df.nunique()
        numeric_summary.to_csv(AUDIT_DIR / f"{name}_numeric_summary.csv")

        lines.append("## Numeric Summary")
        lines.append("")
        lines.append(f"Numeric columns: {len(numeric_df.columns)}")
        lines.append(f"Saved to: outputs/audit/{name}_numeric_summary.csv")
        lines.append("")

        lines.append("## Outlier Check")
        lines.append("")
        for col in numeric_df.columns:
            q1 = numeric_df[col].quantile(0.25)
            q3 = numeric_df[col].quantile(0.75)
            iqr = q3 - q1
            lower = q1 - 1.5 * iqr
            upper = q3 + 1.5 * iqr
            outliers = ((numeric_df[col] < lower) | (numeric_df[col] > upper)).sum()
            lines.append(f"- {col}: {outliers:,} possible outliers")
        lines.append("")

    lines.append("## Leakage Check")
    lines.append("")
    found_leakage = [col for col in LEAKAGE_COLUMNS if col in df.columns]
    if found_leakage:
        for col in found_leakage:
            lines.append(f"- Exclude `{col}` from ML features.")
    else:
        lines.append("No known leakage columns found.")
    lines.append("")

    lines.append("## Feature Reality Check")
    lines.append("")
    safe_features_found = [col for col in MAIN_SAFE_FEATURES if col in df.columns]
    missing_safe_features = [col for col in MAIN_SAFE_FEATURES if col not in df.columns]

    lines.append(f"Safe main features found: {len(safe_features_found)}")
    for col in safe_features_found:
        lines.append(f"- {col}")

    if missing_safe_features:
        lines.append("")
        lines.append("Missing expected main features:")
        for col in missing_safe_features:
            lines.append(f"- {col}")

    lines.append("")

    critical_issues = []

    if TARGET_COLUMN not in df.columns:
        critical_issues.append("Target column friction_level is missing.")

    if df.isna().sum().sum() > 0:
        critical_issues.append("Dataset contains missing values.")

    if "friction_score" in safe_features_found:
        critical_issues.append("friction_score is wrongly included as safe feature.")

    lines.append("## Critical Issues")
    lines.append("")
    if critical_issues:
        for issue in critical_issues:
            lines.append(f"- CRITICAL: {issue}")
    else:
        lines.append("No critical issues found.")
    lines.append("")

    return lines


def main():
    all_lines = []

    if not FULL_DATASET_PATH.exists():
        raise FileNotFoundError(f"Full dataset not found: {FULL_DATASET_PATH}")

    full_df = pd.read_csv(FULL_DATASET_PATH)
    all_lines.extend(audit_dataset(full_df, "gagent_full"))
    all_lines.append("\n---\n")

    if COMMON_DATASET_PATH.exists():
        common_df = pd.read_csv(COMMON_DATASET_PATH)
        all_lines.extend(audit_dataset(common_df, "gagent_common"))
    else:
        all_lines.append("# Common Dataset Audit")
        all_lines.append("")
        all_lines.append("Common feature export not found.")

    output_path = AUDIT_DIR / "dataset_audit_summary.md"
    output_path.write_text("\n".join(all_lines), encoding="utf-8")

    print("Dataset audit completed.")
    print(f"Saved audit summary to: {output_path}")


if __name__ == "__main__":
    main()