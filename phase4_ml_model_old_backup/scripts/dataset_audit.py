from pathlib import Path
import pandas as pd
import numpy as np


BASE_DIR = Path(__file__).resolve().parents[1]

DATASET_PATH = BASE_DIR / "datasets" / "combined_ux_friction_dataset_v2_full_unique.csv"

REPORT_PATH = BASE_DIR / "outputs" / "reports" / "dataset_audit_summary.txt"
CLASS_DISTRIBUTION_PATH = BASE_DIR / "outputs" / "evaluation" / "class_distribution.csv"
SOURCE_DISTRIBUTION_PATH = BASE_DIR / "outputs" / "evaluation" / "source_distribution.csv"

REQUIRED_COLUMNS = [
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

ALLOWED_LABELS = {"Low", "Medium", "High"}

ALLOWED_TASK_COMPLETED_VALUES = {-1, 0, 1}
ALLOWED_ERROR_MESSAGE_CLARITY_VALUES = {-1, 0, 1, 2}

UNKNOWN_ALLOWED_COLUMNS = {
    "scroll_count",
    "keyboard_count",
    "feedback_delay",
    "task_completed",
    "error_message_clarity",
}


def build_distribution_table(df: pd.DataFrame, column_name: str) -> pd.DataFrame:
    distribution = df[column_name].value_counts(dropna=False).reset_index()
    distribution.columns = [column_name, "count"]
    distribution["percentage"] = (distribution["count"] / len(df) * 100).round(2)
    return distribution


def section(title: str) -> str:
    return f"\n{title}\n" + "-" * 80 + "\n"


def main() -> None:
    REPORT_PATH.parent.mkdir(parents=True, exist_ok=True)
    CLASS_DISTRIBUTION_PATH.parent.mkdir(parents=True, exist_ok=True)
    SOURCE_DISTRIBUTION_PATH.parent.mkdir(parents=True, exist_ok=True)

    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    row_count, column_count = df.shape

    existing_columns = list(df.columns)
    missing_required_columns = [col for col in REQUIRED_COLUMNS if col not in existing_columns]
    extra_columns = [col for col in existing_columns if col not in REQUIRED_COLUMNS]
    schema_order_correct = existing_columns == REQUIRED_COLUMNS

    missing_values = df.isna().sum()
    total_missing_values = int(missing_values.sum())

    duplicate_rows = int(df.duplicated().sum())
    duplicate_percentage = round((duplicate_rows / row_count) * 100, 2) if row_count else 0

    data_types = df.dtypes.astype(str)

    class_distribution = build_distribution_table(df, "friction_level")
    source_distribution = build_distribution_table(df, "source_dataset")
    flow_type_distribution = build_distribution_table(df, "flow_type")

    class_distribution.to_csv(CLASS_DISTRIBUTION_PATH, index=False)
    source_distribution.to_csv(SOURCE_DISTRIBUTION_PATH, index=False)

    numeric_columns_available = [col for col in NUMERIC_COLUMNS if col in df.columns]

    numeric_df = df[numeric_columns_available].copy()
    for col in numeric_columns_available:
        numeric_df[col] = pd.to_numeric(numeric_df[col], errors="coerce")

    numeric_summary = numeric_df.describe().T.round(4)

    invalid_numeric_values = {}
    negative_value_check = {}

    for col in numeric_columns_available:
        converted = pd.to_numeric(df[col], errors="coerce")
        invalid_count = int(converted.isna().sum())
        invalid_numeric_values[col] = invalid_count

        if col in UNKNOWN_ALLOWED_COLUMNS:
            invalid_negative_count = int((converted < -1).sum())
        else:
            invalid_negative_count = int((converted < 0).sum())

        negative_value_check[col] = invalid_negative_count

    invalid_labels = sorted(set(df["friction_level"].dropna().unique()) - ALLOWED_LABELS)

    task_completed_values = df["task_completed"].value_counts(dropna=False).sort_index()
    error_message_clarity_values = df["error_message_clarity"].value_counts(dropna=False).sort_index()

    invalid_task_completed = sorted(
        set(pd.to_numeric(df["task_completed"], errors="coerce").dropna().astype(int).unique())
        - ALLOWED_TASK_COMPLETED_VALUES
    )

    invalid_error_message_clarity = sorted(
        set(pd.to_numeric(df["error_message_clarity"], errors="coerce").dropna().astype(int).unique())
        - ALLOWED_ERROR_MESSAGE_CLARITY_VALUES
    )

    report_lines = []

    report_lines.append("=" * 80)
    report_lines.append("GAgent Phase 4 Dataset Audit Summary")
    report_lines.append("=" * 80)
    report_lines.append("")
    report_lines.append(f"Dataset path: {DATASET_PATH}")
    report_lines.append(f"Rows: {row_count}")
    report_lines.append(f"Columns: {column_count}")

    report_lines.append(section("Required Column Check"))
    if not missing_required_columns and not extra_columns:
        report_lines.append("Status: PASSED")
        report_lines.append("All required 14 columns are present.")
    else:
        report_lines.append("Status: FAILED")
        report_lines.append(f"Missing required columns: {missing_required_columns}")
        report_lines.append(f"Extra columns: {extra_columns}")

    report_lines.append(f"Schema order correct: {schema_order_correct}")

    report_lines.append(section("Column List"))
    report_lines.extend(existing_columns)

    report_lines.append(section("Missing Value Check"))
    report_lines.append(f"Total missing values: {total_missing_values}")
    report_lines.append(missing_values.to_string())

    report_lines.append(section("Duplicate Row Check"))
    report_lines.append(f"Duplicate rows: {duplicate_rows}")
    report_lines.append(f"Duplicate percentage: {duplicate_percentage}%")

    report_lines.append(section("Data Type Check"))
    report_lines.append(data_types.to_string())

    report_lines.append(section("Invalid Numeric Value Check"))
    for col, count in invalid_numeric_values.items():
        report_lines.append(f"{col}: {count}")

    report_lines.append(section("Invalid Negative Value Check"))
    report_lines.append("Rule: -1 is allowed only for unknown/not applicable columns.")
    for col, count in negative_value_check.items():
        report_lines.append(f"{col}: {count}")

    report_lines.append(section("Friction Level Distribution"))
    report_lines.append(class_distribution.to_string(index=False))

    report_lines.append(section("Invalid Friction Labels"))
    if invalid_labels:
        report_lines.append(f"Invalid labels found: {invalid_labels}")
    else:
        report_lines.append("No invalid friction labels found.")

    report_lines.append(section("Source Dataset Distribution"))
    report_lines.append(source_distribution.to_string(index=False))

    report_lines.append(section("Flow Type Distribution - Top 30"))
    report_lines.append(flow_type_distribution.head(30).to_string(index=False))

    report_lines.append(section("Numeric Feature Summary"))
    report_lines.append(numeric_summary.to_string())

    report_lines.append(section("Task Completed Value Check"))
    report_lines.append(task_completed_values.to_string())
    if invalid_task_completed:
        report_lines.append(f"Invalid task_completed values: {invalid_task_completed}")
    else:
        report_lines.append("task_completed values are valid.")

    report_lines.append(section("Error Message Clarity Value Check"))
    report_lines.append(error_message_clarity_values.to_string())
    if invalid_error_message_clarity:
        report_lines.append(f"Invalid error_message_clarity values: {invalid_error_message_clarity}")
    else:
        report_lines.append("error_message_clarity values are valid.")

    report_lines.append(section("Audit Decision"))
    audit_passed = (
        not missing_required_columns
        and not extra_columns
        and schema_order_correct
        and total_missing_values == 0
        and duplicate_rows == 0
        and not invalid_labels
        and not invalid_task_completed
        and not invalid_error_message_clarity
        and sum(invalid_numeric_values.values()) == 0
        and sum(negative_value_check.values()) == 0
    )

    if audit_passed:
        report_lines.append("PASSED: Dataset is ready for Phase 4 EDA and model training.")
    else:
        report_lines.append("WARNING: Dataset needs review before model training.")
        report_lines.append("Review missing columns, duplicates, invalid labels, or invalid numeric values.")

    REPORT_PATH.write_text("\n".join(report_lines), encoding="utf-8")

    print("=" * 80)
    print("GAgent Phase 4 Dataset Audit Completed")
    print("=" * 80)
    print(f"Dataset path: {DATASET_PATH}")
    print(f"Rows: {row_count}")
    print(f"Columns: {column_count}")
    print(f"Missing required columns: {missing_required_columns}")
    print(f"Extra columns: {extra_columns}")
    print(f"Schema order correct: {schema_order_correct}")
    print(f"Total missing values: {total_missing_values}")
    print(f"Duplicate rows: {duplicate_rows}")
    print(f"Audit report saved to: {REPORT_PATH}")
    print(f"Class distribution saved to: {CLASS_DISTRIBUTION_PATH}")
    print(f"Source distribution saved to: {SOURCE_DISTRIBUTION_PATH}")

    if audit_passed:
        print("Audit status: PASSED")
    else:
        print("Audit status: REVIEW NEEDED")


if __name__ == "__main__":
    main()