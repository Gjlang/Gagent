import pandas as pd
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]

DATASET_PATH = PROJECT_ROOT / "outputs" / "processed" / "ux_friction_dataset.csv"
VALIDATION_OUTPUT = PROJECT_ROOT / "outputs" / "processed" / "ux_friction_validation_summary.csv"

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
    "friction_level"
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
    "error_message_clarity"
]

def main():
    print("Validating ux_friction_dataset.csv...")

    df = pd.read_csv(DATASET_PATH)

    validation_results = []

    # Check required columns
    missing_columns = [col for col in REQUIRED_COLUMNS if col not in df.columns]

    validation_results.append({
        "check": "Required columns",
        "result": "Pass" if len(missing_columns) == 0 else "Fail",
        "details": "All required columns exist" if len(missing_columns) == 0 else f"Missing: {missing_columns}"
    })

    # Check row count
    validation_results.append({
        "check": "Row count",
        "result": "Pass" if len(df) >= 1000 else "Warning",
        "details": f"Total rows: {len(df)}"
    })

    # Check labels
    valid_labels = {"Low", "Medium", "High"}
    actual_labels = set(df["friction_level"].dropna().unique())

    validation_results.append({
        "check": "Friction labels",
        "result": "Pass" if actual_labels.issubset(valid_labels) else "Fail",
        "details": f"Labels found: {actual_labels}"
    })

    # Check missing values
    missing_values = int(df.isna().sum().sum())

    validation_results.append({
        "check": "Missing values",
        "result": "Pass" if missing_values == 0 else "Warning",
        "details": f"Total missing values: {missing_values}"
    })

    # Check numeric columns
    numeric_issue_cols = []

    for col in NUMERIC_COLUMNS:
        converted = pd.to_numeric(df[col], errors="coerce")
        if converted.isna().sum() > 0:
            numeric_issue_cols.append(col)

    validation_results.append({
        "check": "Numeric columns",
        "result": "Pass" if len(numeric_issue_cols) == 0 else "Fail",
        "details": "All numeric columns valid" if len(numeric_issue_cols) == 0 else f"Issues in: {numeric_issue_cols}"
    })

    # Check class distribution
    class_distribution = df["friction_level"].value_counts().to_dict()

    validation_results.append({
        "check": "Class distribution",
        "result": "Review",
        "details": str(class_distribution)
    })

    # Check source dataset distribution
    source_distribution = df["source_dataset"].value_counts().to_dict()

    validation_results.append({
        "check": "Source dataset distribution",
        "result": "Review",
        "details": str(source_distribution)
    })

    result_df = pd.DataFrame(validation_results)

    result_df.to_csv(VALIDATION_OUTPUT, index=False)

    print("\nValidation completed.")
    print(result_df)

    print(f"\nSaved validation summary:")
    print(VALIDATION_OUTPUT)

if __name__ == "__main__":
    main()