import json
from pathlib import Path

import pandas as pd

ROOT = Path(__file__).resolve().parents[1]
FINAL_DIR = ROOT / "outputs" / "final"
DATASET_PATH = FINAL_DIR / "gagent_full_ux_friction_dataset.csv"
SCHEMA_PATH = FINAL_DIR / "gagent_full_schema.json"
COMMON_EXPORT_PATH = FINAL_DIR / "gagent_common_features_export.csv"

COMMON_FEATURES = [
    "completion_time",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "task_completed",
    "friction_level",
]

SCHEMA = {
    "dataset_name": "gagent_full_ux_friction_dataset",
    "phase": "Phase 3 controlled Playwright dataset",
    "target_column": "friction_level",
    "excluded_from_ml": ["expected_friction_level", "label_mismatch", "route", "seed", "run_id", "source_dataset", "page_url", "screenshot_count"],
    "columns": {
        "flow_type": "Controlled task flow category, not a raw URL.",
        "scenario_type": "Specific UX friction scenario simulated by the dummy website.",
        "viewport_type": "desktop, tablet, or mobile.",
        "network_condition": "fast, normal, or slow controlled test context.",
        "task_completed": "1 if the target task reached success state, otherwise 0.",
        "task_failed": "1 if task was not completed, otherwise 0.",
        "completion_time": "Total task time in seconds.",
        "click_count": "Browser-observed click interactions.",
        "scroll_count": "Browser-observed scroll interactions.",
        "keyboard_count": "Browser-observed keydown interactions.",
        "retry_count": "Recovery attempts after failure, obstruction, validation, or wrong path.",
        "error_count": "Errors observed during task execution.",
        "failed_clicks": "Clicks on fake, disabled, blocked, or failed controls.",
        "unnecessary_clicks": "Clicks not required for the ideal task path.",
        "path_deviation_score": "0-10 score measuring deviation from the expected path.",
        "page_load_time_ms": "Navigation load event timing from browser Performance API.",
        "dom_content_loaded_ms": "DOM content loaded timing from browser Performance API.",
        "time_to_first_byte_ms": "Response-start timing from browser Performance API.",
        "feedback_delay_ms": "Time from user action to visible status feedback.",
        "interaction_to_next_paint_ms": "Measured delay from interaction to next rendered frame/event duration.",
        "cumulative_layout_shift": "CLS collected by browser PerformanceObserver.",
        "error_message_present": "1 if an error message is visible or detected.",
        "error_message_clarity": "-1 none, 0 vague, 1 partial, 2 clear.",
        "popup_detected": "1 if a popup/modal is present during the run.",
        "cookie_banner_detected": "1 if a cookie banner is present during the run.",
        "overlay_blocks_cta": "1 if an overlay blocks the target CTA.",
        "friction_score": "Weighted scoring value used for label assignment.",
        "friction_level": "Final target label: Low, Medium, or High."
    }
}


def main():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(f"Final dataset not found: {DATASET_PATH}")
    FINAL_DIR.mkdir(parents=True, exist_ok=True)
    df = pd.read_csv(DATASET_PATH)
    missing = [column for column in COMMON_FEATURES if column not in df.columns]
    if missing:
        raise ValueError(f"Missing common feature columns: {missing}")
    SCHEMA_PATH.write_text(json.dumps(SCHEMA, indent=2), encoding="utf-8")
    df[COMMON_FEATURES].to_csv(COMMON_EXPORT_PATH, index=False)
    print(f"Schema saved to: {SCHEMA_PATH}")
    print(f"Common feature export saved to: {COMMON_EXPORT_PATH}")


if __name__ == "__main__":
    main()
