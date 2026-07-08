from pathlib import Path
from typing import List

import pandas as pd


ROOT_DIR = Path(__file__).resolve().parents[1]
DATASET_PATH = ROOT_DIR / "datasets" / "android_appium_dataset_10005_rows_labeled.csv"
AUDIT_DIR = ROOT_DIR / "outputs" / "audit"
AUDIT_REPORT_PATH = AUDIT_DIR / "android_dataset_audit_report.md"

TARGET_COLUMN = "friction_level"

KNOWN_LEAKAGE_COLUMNS = {
    "friction_level",
    "friction_level_prediction",
    "scenario_type",
    "run_index",
    "target_type",
    "id",
    "row_id",
    "timestamp",
    "created_at",
    "updated_at",
}

EXPECTED_LABELS = {"Low", "Medium", "High"}
EXPECTED_SCENARIOS = {"good", "medium", "bad"}

MAX_ACCEPTABLE_CRASH_RATE = 0.20
MIN_ACCEPTABLE_COMPLETION_RATE = 0.20


def markdown_series_table(series: pd.Series, title: str) -> List[str]:
    lines = [f"## {title}", "", "| Value | Count |", "|---|---:|"]

    if series.empty:
        lines.append("| None | 0 |")
    else:
        for key, value in series.items():
            lines.append(f"| {key} | {value} |")

    lines.append("")
    return lines


def main() -> None:
    AUDIT_DIR.mkdir(parents=True, exist_ok=True)

    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Dataset not found: {DATASET_PATH}")

    df = pd.read_csv(DATASET_PATH)

    missing_counts = df.isna().sum().sort_values(ascending=False)
    duplicate_count = int(df.duplicated().sum())
    constant_columns = [col for col in df.columns if df[col].nunique(dropna=False) <= 1]
    numeric_columns = df.select_dtypes(include="number").columns.tolist()
    categorical_columns = [col for col in df.columns if col not in numeric_columns]
    leakage_columns = [col for col in df.columns if col in KNOWN_LEAKAGE_COLUMNS]

    invalid_issues = []
    serious_quality_issues = []

    if TARGET_COLUMN not in df.columns:
        invalid_issues.append("Missing required target column: friction_level")
    else:
        labels = set(df[TARGET_COLUMN].dropna().astype(str).unique())

        if labels - EXPECTED_LABELS:
            invalid_issues.append(f"Unexpected friction_level labels: {sorted(labels - EXPECTED_LABELS)}")

        if EXPECTED_LABELS - labels:
            invalid_issues.append(f"Missing expected friction_level labels: {sorted(EXPECTED_LABELS - labels)}")

    if "scenario_type" in df.columns:
        scenarios = set(df["scenario_type"].dropna().astype(str).unique())

        if scenarios - EXPECTED_SCENARIOS:
            invalid_issues.append(f"Unexpected scenario_type values: {sorted(scenarios - EXPECTED_SCENARIOS)}")

    for col in numeric_columns:
        numeric_values = pd.to_numeric(df[col], errors="coerce")
        if (numeric_values.dropna() < 0).any():
            invalid_issues.append(f"Negative values detected in numeric column: {col}")

    crash_rate = None
    completion_rate = None

    if "crash_detected" in df.columns:
        crash_rate = float(pd.to_numeric(df["crash_detected"], errors="coerce").fillna(0).mean())
        if crash_rate > MAX_ACCEPTABLE_CRASH_RATE:
            serious_quality_issues.append(
                f"crash_detected rate is {crash_rate:.2%}, above acceptable threshold {MAX_ACCEPTABLE_CRASH_RATE:.0%}."
            )

    if "task_completed" in df.columns:
        completion_rate = float(pd.to_numeric(df["task_completed"], errors="coerce").fillna(0).mean())
        if completion_rate < MIN_ACCEPTABLE_COMPLETION_RATE:
            serious_quality_issues.append(
                f"task_completed rate is {completion_rate:.2%}, below acceptable threshold {MIN_ACCEPTABLE_COMPLETION_RATE:.0%}."
            )

    friction_prediction_note = "Column friction_level_prediction not found."

    if "friction_level_prediction" in df.columns:
        non_empty = int(df["friction_level_prediction"].notna().sum())

        if non_empty == 0:
            friction_prediction_note = (
                "friction_level_prediction exists and is fully empty. Correct: exclude it from training."
            )
        else:
            friction_prediction_note = (
                f"friction_level_prediction has {non_empty} non-empty values. Exclude it from training."
            )

    serious_missing = missing_counts[
        (missing_counts > 0) & (missing_counts.index != "friction_level_prediction")
    ]

    ready = (
        TARGET_COLUMN in df.columns
        and df[TARGET_COLUMN].notna().all()
        and not invalid_issues
        and serious_missing.empty
        and duplicate_count == 0
        and not serious_quality_issues
    )

    lines = [
        "# Android Appium Dataset Audit Report",
        "",
        f"Dataset path: `{DATASET_PATH}`",
        f"Shape: **{df.shape[0]} rows × {df.shape[1]} columns**",
        f"Ready for training: **{'YES' if ready else 'NO'}**",
        "",
        "## Column List",
        "",
    ]

    lines.extend([f"- `{col}`" for col in df.columns])
    lines.append("")

    lines.extend(markdown_series_table(missing_counts[missing_counts > 0], "Missing Values"))

    lines.extend(
        [
            "## Duplicate Rows",
            "",
            f"Duplicate row count: **{duplicate_count}**",
            "",
            "## Constant Columns",
            "",
        ]
    )
    lines.extend([f"- `{col}`" for col in constant_columns] or ["- None"])
    lines.append("")

    if TARGET_COLUMN in df.columns:
        lines.extend(markdown_series_table(df[TARGET_COLUMN].value_counts(dropna=False), "friction_level Distribution"))

    if "scenario_type" in df.columns:
        lines.extend(markdown_series_table(df["scenario_type"].value_counts(dropna=False), "scenario_type Distribution"))

    if "flow_type" in df.columns:
        lines.extend(markdown_series_table(df["flow_type"].value_counts(dropna=False), "flow_type Distribution"))

    lines.extend(["## Quality Signals", ""])

    if crash_rate is not None:
        lines.append(f"- crash_detected rate: **{crash_rate:.2%}**")

    if completion_rate is not None:
        lines.append(f"- task_completed rate: **{completion_rate:.2%}**")

    lines.extend(
        [
            "",
            "## Numeric Column Summary",
            "",
            "```text",
            df[numeric_columns].describe().transpose().round(4).to_string() if numeric_columns else "No numeric columns found.",
            "```",
            "",
            "## Categorical Columns",
            "",
        ]
    )

    lines.extend([f"- `{col}`: {df[col].nunique(dropna=False)} unique values" for col in categorical_columns])

    lines.extend(["", "## Leakage Columns to Exclude", ""])
    lines.extend([f"- `{col}`" for col in leakage_columns] or ["- None detected"])

    lines.extend(
        [
            "",
            "## friction_level_prediction Check",
            "",
            friction_prediction_note,
            "",
            "## Invalid Issues",
            "",
        ]
    )
    lines.extend([f"- {issue}" for issue in invalid_issues] or ["- None"])

    lines.extend(["", "## Serious Quality Issues", ""])
    lines.extend([f"- {issue}" for issue in serious_quality_issues] or ["- None"])

    lines.extend(
        [
            "",
            "## Final Audit Decision",
            "",
            f"Ready for training: **{'YES' if ready else 'NO'}**",
            "",
        ]
    )

    AUDIT_REPORT_PATH.write_text("\n".join(lines), encoding="utf-8")

    print("Android dataset audit completed")
    print(f"Dataset shape: {df.shape[0]} rows x {df.shape[1]} columns")
    print(f"Duplicate rows: {duplicate_count}")

    if TARGET_COLUMN in df.columns:
        print("friction_level distribution:")
        print(df[TARGET_COLUMN].value_counts())

    print(f"Constant columns: {constant_columns}")
    print(f"Leakage columns to exclude: {leakage_columns}")
    print(friction_prediction_note)

    if crash_rate is not None:
        print(f"crash_detected rate: {crash_rate:.2%}")

    if completion_rate is not None:
        print(f"task_completed rate: {completion_rate:.2%}")

    print(f"Ready for training: {'YES' if ready else 'NO'}")
    print(f"Audit report saved to: {AUDIT_REPORT_PATH}")

    if not ready:
        raise SystemExit("Dataset is not ready for training. Fix audit issues before training.")


if __name__ == "__main__":
    main()