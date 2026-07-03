from pathlib import Path

import pandas as pd


SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parents[2]

DATASET_PATH = PROJECT_ROOT / "phase3_web_automation" / "playwright_agent" / "outputs" / "final_export" / "combined_ux_friction_dataset_v2.csv"

REPORT_PATH = PROJECT_ROOT / "phase3_web_automation" / "playwright_agent" / "outputs" / "final_export" / "final_dataset_audit_report.txt"
CLASS_DIST_PATH = PROJECT_ROOT / "phase3_web_automation" / "playwright_agent" / "outputs" / "final_export" / "class_distribution_v2.csv"
SOURCE_DIST_PATH = PROJECT_ROOT / "phase3_web_automation" / "playwright_agent" / "outputs" / "final_export" / "source_distribution_v2.csv"

FINAL_COLUMNS = [
    "source_dataset",
    "flow_type",
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
    "friction_level",
]

NUMERIC_COLUMNS = [
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

# -1 is allowed as unknown sentinel.
ALLOW_MINUS_ONE_COLUMNS = {
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
}


def main():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    report = []
    report.append("FINAL DATASET AUDIT REPORT V2")
    report.append("=" * 40)
    report.append(f"Dataset path: {DATASET_PATH}")
    report.append(f"Row count: {len(df)}")
    report.append(f"Column count: {len(df.columns)}")

    missing_required = [col for col in FINAL_COLUMNS if col not in df.columns]
    extra_columns = [col for col in df.columns if col not in FINAL_COLUMNS]

    report.append(f"Missing required columns: {missing_required}")
    report.append(f"Extra columns: {extra_columns}")
    report.append(f"Schema order correct: {list(df.columns) == FINAL_COLUMNS}")

    total_missing = int(df.isna().sum().sum())
    duplicate_rows = int(df.duplicated().sum())
    unique_rows = len(df.drop_duplicates())

    report.append(f"Total missing values: {total_missing}")
    report.append(f"Duplicate rows: {duplicate_rows}")
    report.append(f"Unique rows: {unique_rows}")

    report.append("\nClass distribution:")
    class_dist = df["friction_level"].value_counts(dropna=False).reset_index()
    class_dist.columns = ["friction_level", "row_count"]
    report.append(class_dist.to_string(index=False))

    report.append("\nSource distribution:")
    source_dist = df["source_dataset"].value_counts(dropna=False).reset_index()
    source_dist.columns = ["source_dataset", "row_count"]
    report.append(source_dist.to_string(index=False))

    report.append("\nNumeric feature ranges:")
    invalid_negative_summary = []

    for col in NUMERIC_COLUMNS:
        df[col] = pd.to_numeric(df[col], errors="coerce")
        min_value = df[col].min()
        max_value = df[col].max()
        report.append(f"{col}: min={min_value}, max={max_value}")

        if col in ALLOW_MINUS_ONE_COLUMNS:
            invalid_count = int((df[col] < -1).sum())
        else:
            invalid_count = int((df[col] < 0).sum())

        if invalid_count > 0:
            invalid_negative_summary.append((col, invalid_count))

    report.append("\nInvalid negative values:")
    if invalid_negative_summary:
        for col, count in invalid_negative_summary:
            report.append(f"{col}: {count}")
    else:
        report.append("None")

    achieved_50k = unique_rows >= 50_000
    acceptable_for_phase4 = (
        list(df.columns) == FINAL_COLUMNS
        and total_missing == 0
        and duplicate_rows == 0
        and len(set(df["friction_level"].unique()) - {"Low", "Medium", "High"}) == 0
        and len(df["friction_level"].unique()) == 3
    )

    report.append(f"\n50K unique rows achieved: {achieved_50k}")
    report.append(f"Acceptable for Phase 4 EDA: {acceptable_for_phase4}")

    if acceptable_for_phase4:
        report.append("\nDecision: Yes, proceed to Phase 4 dataset audit / EDA.")
    else:
        report.append("\nDecision: Not yet, fix dataset issue first.")

    REPORT_PATH.write_text("\n".join(report), encoding="utf-8")
    class_dist.to_csv(CLASS_DIST_PATH, index=False)
    source_dist.to_csv(SOURCE_DIST_PATH, index=False)

    print("\n".join(report))
    print(f"\nSaved report: {REPORT_PATH}")
    print(f"Saved class distribution: {CLASS_DIST_PATH}")
    print(f"Saved source distribution: {SOURCE_DIST_PATH}")


if __name__ == "__main__":
    main()