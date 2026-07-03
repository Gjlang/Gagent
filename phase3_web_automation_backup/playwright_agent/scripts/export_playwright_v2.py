from pathlib import Path

import pandas as pd


SCRIPT_DIR = Path(__file__).resolve().parent
PLAYWRIGHT_ROOT = SCRIPT_DIR.parent
RAW_INPUT = PLAYWRIGHT_ROOT / "outputs" / "datasets" / "agent_generated_ux_dataset.csv"
FALLBACK_INPUT = PLAYWRIGHT_ROOT / "outputs" / "final_export" / "ux_friction_dataset.csv"
OUTPUT_DIR = PLAYWRIGHT_ROOT / "outputs" / "final_export"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

OUTPUT_PATH = OUTPUT_DIR / "playwright_ux_dataset_v2.csv"

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

VALID_LABELS = {"Low", "Medium", "High"}


def choose_input() -> Path:
    if RAW_INPUT.exists():
        return RAW_INPUT
    if FALLBACK_INPUT.exists():
        return FALLBACK_INPUT
    raise FileNotFoundError("No Playwright dataset found. Run Playwright first.")


def main():
    input_path = choose_input()
    print(f"Input: {input_path}")

    df = pd.read_csv(input_path)

    if "source_dataset" not in df.columns and "source" in df.columns:
        df["source_dataset"] = df["source"]

    if "friction_level" not in df.columns and "expected_friction_level" in df.columns:
        df["friction_level"] = df["expected_friction_level"]

    for col in FINAL_COLUMNS:
        if col not in df.columns:
            if col in ["source_dataset", "flow_type"]:
                df[col] = "playwright_dummy_website"
            elif col == "friction_level":
                df[col] = "Unknown"
            else:
                df[col] = -1

    df = df[FINAL_COLUMNS].copy()

    for col in NUMERIC_COLUMNS:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(-1)

    invalid_labels = set(df["friction_level"].unique()) - VALID_LABELS
    if invalid_labels:
        raise ValueError(f"Invalid friction labels found: {invalid_labels}")

    before_rows = len(df)
    duplicate_rows = int(df.duplicated().sum())
    df = df.drop_duplicates().sample(frac=1, random_state=42).reset_index(drop=True)

    df.to_csv(OUTPUT_PATH, index=False)

    print("\nPlaywright V2 export completed.")
    print(f"Rows before deduplication: {before_rows}")
    print(f"Duplicate rows removed: {duplicate_rows}")
    print(f"Rows after deduplication: {len(df)}")
    print("\nClass distribution:")
    print(df["friction_level"].value_counts())
    print("\nSource distribution:")
    print(df["source_dataset"].value_counts())
    print(f"\nSaved: {OUTPUT_PATH}")


if __name__ == "__main__":
    main()