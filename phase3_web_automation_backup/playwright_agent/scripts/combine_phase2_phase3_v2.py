from pathlib import Path
import pandas as pd


SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parents[2]

PHASE2_PATH = (
    PROJECT_ROOT
    / "phase2_dataset_preparation"
    / "outputs"
    / "processed_v2"
    / "phase2_unique_ux_dataset.csv"
)

PHASE3_PATH = (
    PROJECT_ROOT
    / "phase3_web_automation"
    / "playwright_agent"
    / "outputs"
    / "final_export"
    / "playwright_ux_dataset_v2.csv"
)

OUTPUT_DIR = (
    PROJECT_ROOT
    / "phase3_web_automation"
    / "playwright_agent"
    / "outputs"
    / "final_export"
)

OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

OUTPUT_PATH = OUTPUT_DIR / "combined_ux_friction_dataset_v2_full_unique.csv"

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


def validate_schema(df: pd.DataFrame, name: str):
    if list(df.columns) != FINAL_COLUMNS:
        raise ValueError(
            f"{name} schema mismatch.\n"
            f"Expected: {FINAL_COLUMNS}\n"
            f"Found: {list(df.columns)}"
        )


def main():
    if not PHASE2_PATH.exists():
        raise FileNotFoundError(f"Missing Phase 2 unique dataset: {PHASE2_PATH}")

    if not PHASE3_PATH.exists():
        raise FileNotFoundError(f"Missing Playwright V2 dataset: {PHASE3_PATH}")

    phase2 = pd.read_csv(PHASE2_PATH)
    phase3 = pd.read_csv(PHASE3_PATH)

    validate_schema(phase2, "Phase 2 unique")
    validate_schema(phase3, "Phase 3 Playwright")

    print("Phase 2 unique rows:", len(phase2))
    print("Phase 2 duplicate rows:", phase2.duplicated().sum())

    print("Phase 3 rows:", len(phase3))
    print("Phase 3 duplicate rows:", phase3.duplicated().sum())

    combined = pd.concat([phase2, phase3], ignore_index=True)

    original_rows = len(combined)
    duplicate_rows = int(combined.duplicated().sum())

    combined = combined.drop_duplicates()
    combined = combined.sample(frac=1, random_state=42).reset_index(drop=True)

    combined.to_csv(OUTPUT_PATH, index=False)

    print("\nCombined FULL UNIQUE V2 dataset created.")
    print(f"Original rows before final dedup: {original_rows}")
    print(f"Duplicate rows removed: {duplicate_rows}")
    print(f"Final unique rows saved: {len(combined)}")
    print(f"Column count: {len(combined.columns)}")

    print("\nClass distribution:")
    print(combined["friction_level"].value_counts())

    print("\nSource distribution:")
    print(combined["source_dataset"].value_counts())

    print(f"\nSaved to: {OUTPUT_PATH}")


if __name__ == "__main__":
    main()