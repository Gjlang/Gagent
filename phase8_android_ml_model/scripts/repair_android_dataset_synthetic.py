r"""
repair_android_dataset_synthetic.py

Purpose:
Regenerate a cleaned controlled synthetic Android UX-friction dataset using the
existing Android Appium dataset as a structural template.

This script preserves:
- row count
- column structure
- flow_type
- scenario_type
- friction_level
- run_index, if present

It regenerates broken UX metric columns using label-based, flow-based, and
scenario-consistency logic after long-run Android emulator instability was found.

Run from:
D:\FYP\GAgent\GAgent\phase8_android_ml_model

Command:
python scripts\repair_android_dataset_synthetic.py
"""

from __future__ import annotations

from pathlib import Path
from typing import Any, Dict, Tuple

import numpy as np
import pandas as pd


# -----------------------------------------------------------------------------
# Paths
# -----------------------------------------------------------------------------
PROJECT_ROOT = Path(__file__).resolve().parents[1]
INPUT_PATH = PROJECT_ROOT / "datasets" / "android_appium_dataset_10005_rows_labeled.csv"
OUTPUT_PATH = PROJECT_ROOT / "datasets" / "android_appium_dataset_10005_rows_labeled_cleaned_synthetic.csv"
REPORT_PATH = PROJECT_ROOT / "outputs" / "audit" / "android_cleaned_synthetic_dataset_report.md"

RANDOM_SEED = 42


# -----------------------------------------------------------------------------
# Required schema
# -----------------------------------------------------------------------------
REQUIRED_COLUMNS = [
    "target_type",
    "flow_type",
    "scenario_type",
    "device_type",
    "platform_name",
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
    "app_launch_time_ms",
    "screen_load_time_ms",
    "feedback_delay_ms",
    "interaction_response_time_ms",
    "finish_time_ms",
    "error_message_present",
    "error_message_clarity",
    "popup_detected",
    "overlay_blocks_action",
    "timeout_occurred",
    "crash_detected",
    "anr_detected",
    "friction_level_prediction",
    "run_index",
    "friction_level",
]

METRIC_COLUMNS_TO_REGENERATE = [
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
    "app_launch_time_ms",
    "screen_load_time_ms",
    "feedback_delay_ms",
    "interaction_response_time_ms",
    "finish_time_ms",
    "error_message_present",
    "error_message_clarity",
    "popup_detected",
    "overlay_blocks_action",
    "timeout_occurred",
    "crash_detected",
    "anr_detected",
]

LEAKAGE_COLUMNS_TO_EXCLUDE = [
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
]


# -----------------------------------------------------------------------------
# Synthetic UX-friction rules
# -----------------------------------------------------------------------------
LABEL_CONFIG: Dict[str, Dict[str, Any]] = {
    "Low": {
        "task_completed_p": 0.95,
        "crash_p": 0.005,
        "timeout_p": 0.010,
        "popup_p": 0.035,
        "overlay_p_if_popup": 0.20,
        "overlay_p_without_popup": 0.003,
        "anr_p": 0.003,
        "completion_range": (3.0, 8.0, 5.0),
        "screen_load_range": (300, 1000, 550),
        "feedback_delay_range": (100, 700, 250),
        "response_time_range": (100, 800, 300),
        "app_launch_range": (450, 1300, 750),
        "path_range": (0.00, 0.18, 0.06),
    },
    "Medium": {
        "task_completed_p": 0.82,
        "crash_p": 0.025,
        "timeout_p": 0.110,
        "popup_p": 0.330,
        "overlay_p_if_popup": 0.55,
        "overlay_p_without_popup": 0.040,
        "anr_p": 0.035,
        "completion_range": (8.0, 15.0, 11.0),
        "screen_load_range": (1000, 2500, 1600),
        "feedback_delay_range": (700, 2000, 1200),
        "response_time_range": (800, 2500, 1400),
        "app_launch_range": (700, 2200, 1200),
        "path_range": (0.12, 0.58, 0.32),
    },
    "High": {
        "task_completed_p": 0.50,
        "crash_p": 0.095,
        "timeout_p": 0.440,
        "popup_p": 0.620,
        "overlay_p_if_popup": 0.72,
        "overlay_p_without_popup": 0.120,
        "anr_p": 0.190,
        "completion_range": (15.0, 35.0, 24.0),
        "screen_load_range": (2500, 7000, 4300),
        "feedback_delay_range": (2000, 7000, 4200),
        "response_time_range": (2500, 8000, 4700),
        "app_launch_range": (1000, 4000, 2300),
        "path_range": (0.40, 1.00, 0.72),
    },
}

FLOW_CONFIG: Dict[str, Dict[str, Any]] = {
    "login": {
        "keyboard_range": (2, 2),
        "click_range": (2, 5),
        "scroll_range": (0, 1),
        "time_multiplier": 1.00,
        "error_boost": 0.08,
        "path_boost": 0.00,
        "response_multiplier": 1.00,
    },
    "signup": {
        "keyboard_range": (3, 5),
        "click_range": (3, 7),
        "scroll_range": (0, 2),
        "time_multiplier": 1.25,
        "error_boost": 0.18,
        "path_boost": 0.03,
        "response_multiplier": 1.05,
    },
    "search": {
        "keyboard_range": (1, 2),
        "click_range": (2, 6),
        "scroll_range": (0, 3),
        "time_multiplier": 1.08,
        "error_boost": 0.05,
        "path_boost": 0.10,
        "response_multiplier": 1.15,
    },
    "button_click": {
        "keyboard_range": (0, 0),
        "click_range": (1, 5),
        "scroll_range": (0, 1),
        "time_multiplier": 0.85,
        "error_boost": 0.05,
        "path_boost": 0.02,
        "response_multiplier": 1.30,
    },
    "form_submit": {
        "keyboard_range": (2, 5),
        "click_range": (3, 8),
        "scroll_range": (0, 2),
        "time_multiplier": 1.35,
        "error_boost": 0.22,
        "path_boost": 0.04,
        "response_multiplier": 1.10,
    },
}

DEFAULT_FLOW_CONFIG = {
    "keyboard_range": (0, 3),
    "click_range": (2, 6),
    "scroll_range": (0, 2),
    "time_multiplier": 1.00,
    "error_boost": 0.08,
    "path_boost": 0.03,
    "response_multiplier": 1.00,
}

SCENARIO_TO_LABEL = {
    "good": "Low",
    "medium": "Medium",
    "bad": "High",
}


# -----------------------------------------------------------------------------
# Helper functions
# -----------------------------------------------------------------------------
def validate_required_columns(df: pd.DataFrame) -> None:
    missing = [col for col in REQUIRED_COLUMNS if col not in df.columns]
    if missing:
        raise ValueError(
            "Missing required columns in input dataset:\n"
            + "\n".join(f"- {col}" for col in missing)
        )


def binary_rate(df: pd.DataFrame, column: str) -> float:
    if column not in df.columns:
        return float("nan")
    values = pd.to_numeric(df[column], errors="coerce").fillna(0)
    return float(values.mean())


def fmt_rate(value: float) -> str:
    if pd.isna(value):
        return "N/A"
    return f"{value:.2%}"


def triangular_float(
    rng: np.random.Generator,
    low: float,
    high: float,
    mode: float,
    decimals: int = 2,
) -> float:
    value = rng.triangular(low, mode, high)
    return round(float(value), decimals)


def triangular_int(
    rng: np.random.Generator,
    low: int,
    high: int,
    mode: int | None = None,
) -> int:
    if low == high:
        return int(low)
    if mode is None:
        mode = int(round((low + high) / 2))
    value = rng.triangular(low, mode, high + 1)
    return int(np.clip(round(value), low, high))


def clamp(value: float, low: float, high: float) -> float:
    return float(max(low, min(high, value)))


def bernoulli(rng: np.random.Generator, probability: float) -> int:
    return int(rng.random() < clamp(probability, 0.0, 1.0))


def value_counts_markdown(series: pd.Series) -> str:
    counts = series.value_counts(dropna=False)
    if counts.empty:
        return "- No values found"
    return "\n".join(f"- {index}: {count}" for index, count in counts.items())


def label_from_row(row: pd.Series) -> str:
    label = str(row.get("friction_level", "")).strip()
    if label in LABEL_CONFIG:
        return label

    scenario = str(row.get("scenario_type", "")).strip().lower()
    mapped = SCENARIO_TO_LABEL.get(scenario)
    if mapped:
        return mapped

    raise ValueError(
        f"Cannot infer friction level for row index {row.name}. "
        f"friction_level={row.get('friction_level')}, scenario_type={row.get('scenario_type')}"
    )


def get_flow_config(flow_type: Any) -> Dict[str, Any]:
    flow = str(flow_type).strip().lower()
    return FLOW_CONFIG.get(flow, DEFAULT_FLOW_CONFIG)


# -----------------------------------------------------------------------------
# Row generation logic
# -----------------------------------------------------------------------------
def generate_row_metrics(
    row: pd.Series,
    rng: np.random.Generator,
) -> Dict[str, Any]:
    label = label_from_row(row)
    flow = str(row.get("flow_type", "")).strip().lower()
    label_cfg = LABEL_CONFIG[label]
    flow_cfg = get_flow_config(flow)

    # Controlled ambiguity: some rows intentionally overlap nearby friction levels.
    # This prevents an unrealistically perfect dataset while preserving label logic.
    ambiguity = rng.random() < 0.08
    ambiguity_direction = 0
    if ambiguity:
        if label == "Low":
            ambiguity_direction = 1
        elif label == "Medium":
            ambiguity_direction = int(rng.choice([-1, 1]))
        elif label == "High":
            ambiguity_direction = -1

    # Event probabilities are label-based, then adjusted by flow behavior.
    popup_p = label_cfg["popup_p"]
    timeout_p = label_cfg["timeout_p"]
    crash_p = label_cfg["crash_p"]
    anr_p = label_cfg["anr_p"]

    if flow in {"signup", "form_submit"}:
        timeout_p += 0.025 if label != "Low" else 0.005
    if flow == "search":
        timeout_p += 0.030 if label != "Low" else 0.005
    if flow == "button_click":
        popup_p -= 0.030

    if ambiguity_direction > 0:
        popup_p += 0.08
        timeout_p += 0.05
        anr_p += 0.02
    elif ambiguity_direction < 0:
        popup_p -= 0.08
        timeout_p -= 0.05
        anr_p -= 0.02

    popup_detected = bernoulli(rng, popup_p)
    if popup_detected:
        overlay_blocks_action = bernoulli(rng, label_cfg["overlay_p_if_popup"])
    else:
        overlay_blocks_action = bernoulli(rng, label_cfg["overlay_p_without_popup"])
        if overlay_blocks_action and rng.random() < 0.90:
            popup_detected = 1

    timeout_occurred = bernoulli(rng, timeout_p)
    crash_detected = bernoulli(rng, crash_p)

    # ANR and crash are related but not identical.
    if crash_detected:
        anr_detected = bernoulli(rng, max(0.10, anr_p * 1.25))
    else:
        anr_detected = bernoulli(rng, anr_p)

    # Error-message logic.
    if label == "Low":
        error_present_p = 0.04
    elif label == "Medium":
        error_present_p = 0.33
    else:
        error_present_p = 0.67

    error_present_p += flow_cfg["error_boost"]
    error_present_p += 0.12 if timeout_occurred else 0.00
    error_present_p += 0.18 if crash_detected else 0.00
    error_present_p += 0.12 if anr_detected else 0.00
    error_present_p += 0.07 if overlay_blocks_action else 0.00
    if ambiguity_direction > 0:
        error_present_p += 0.05
    elif ambiguity_direction < 0:
        error_present_p -= 0.05

    error_message_present = bernoulli(rng, error_present_p)

    if not error_message_present:
        error_message_clarity = "none"
    else:
        if label == "Low":
            error_message_clarity = rng.choice(["clear", "medium", "vague"], p=[0.70, 0.25, 0.05])
        elif label == "Medium":
            error_message_clarity = rng.choice(["clear", "medium", "vague"], p=[0.42, 0.40, 0.18])
        else:
            error_message_clarity = rng.choice(["clear", "medium", "vague"], p=[0.18, 0.30, 0.52])

    # Task completion logic. Severe events reduce completion probability.
    task_completed_p = label_cfg["task_completed_p"]
    task_completed_p -= 0.24 if timeout_occurred else 0.00
    task_completed_p -= 0.65 if crash_detected else 0.00
    task_completed_p -= 0.30 if anr_detected else 0.00
    task_completed_p -= 0.12 if overlay_blocks_action else 0.00
    task_completed_p -= 0.05 if error_message_clarity == "vague" else 0.00
    task_completed_p -= 0.03 if flow in {"signup", "form_submit"} else 0.00

    if ambiguity_direction > 0:
        task_completed_p -= 0.06
    elif ambiguity_direction < 0:
        task_completed_p += 0.08

    task_completed = bernoulli(rng, task_completed_p)

    # A detected crash should almost always mean incomplete task.
    if crash_detected and rng.random() < 0.94:
        task_completed = 0
    if anr_detected and rng.random() < 0.55:
        task_completed = 0

    task_failed = int(not bool(task_completed))

    # Timing values.
    completion_low, completion_high, completion_mode = label_cfg["completion_range"]
    completion_time = triangular_float(
        rng,
        completion_low,
        completion_high,
        completion_mode,
        decimals=2,
    )
    completion_time *= float(flow_cfg["time_multiplier"])

    if popup_detected:
        completion_time += triangular_float(rng, 0.5, 2.5, 1.2)
    if overlay_blocks_action:
        completion_time += triangular_float(rng, 1.0, 5.0, 2.2)
    if timeout_occurred:
        completion_time += triangular_float(rng, 5.0, 14.0, 8.0)
    if crash_detected:
        completion_time += triangular_float(rng, 4.0, 12.0, 7.0)
    if anr_detected:
        completion_time += triangular_float(rng, 4.0, 12.0, 7.5)
    if task_failed:
        completion_time += triangular_float(rng, 1.0, 6.0, 2.5)

    if ambiguity_direction > 0:
        completion_time *= rng.uniform(1.05, 1.18)
    elif ambiguity_direction < 0:
        completion_time *= rng.uniform(0.82, 0.96)

    completion_time = round(clamp(completion_time, 2.0, 65.0), 2)

    app_launch_time_ms = triangular_int(rng, *label_cfg["app_launch_range"])
    screen_load_time_ms = triangular_int(rng, *label_cfg["screen_load_range"])
    feedback_delay_ms = triangular_int(rng, *label_cfg["feedback_delay_range"])
    interaction_response_time_ms = triangular_int(rng, *label_cfg["response_time_range"])

    interaction_response_time_ms = int(interaction_response_time_ms * float(flow_cfg["response_multiplier"]))

    if popup_detected:
        feedback_delay_ms += triangular_int(rng, 100, 900, 300)
    if overlay_blocks_action:
        feedback_delay_ms += triangular_int(rng, 300, 1800, 800)
        interaction_response_time_ms += triangular_int(rng, 300, 1700, 800)
    if timeout_occurred:
        screen_load_time_ms += triangular_int(rng, 800, 3500, 1800)
        feedback_delay_ms += triangular_int(rng, 1000, 4500, 2500)
        interaction_response_time_ms += triangular_int(rng, 1000, 4500, 2600)
    if crash_detected:
        app_launch_time_ms += triangular_int(rng, 500, 2800, 1300)
        screen_load_time_ms += triangular_int(rng, 500, 2500, 1100)
    if anr_detected:
        feedback_delay_ms += triangular_int(rng, 1200, 4500, 2400)
        interaction_response_time_ms += triangular_int(rng, 1500, 5000, 2800)

    # Count metrics.
    keyboard_low, keyboard_high = flow_cfg["keyboard_range"]
    keyboard_count = triangular_int(rng, keyboard_low, keyboard_high)
    if label == "High" and flow in {"signup", "form_submit", "search"} and rng.random() < 0.25:
        keyboard_count += 1

    click_low, click_high = flow_cfg["click_range"]
    click_count = triangular_int(rng, click_low, click_high)

    if label == "Low":
        retry_count = int(rng.choice([0, 0, 0, 0, 1]))
    elif label == "Medium":
        retry_count = int(np.clip(rng.poisson(1.1), 0, 3))
    else:
        retry_count = int(np.clip(1 + rng.poisson(2.1), 1, 6))

    if overlay_blocks_action:
        retry_count += triangular_int(rng, 1, 2)
    if timeout_occurred:
        retry_count += triangular_int(rng, 1, 3, 1)
    if crash_detected or anr_detected:
        retry_count += triangular_int(rng, 0, 2)
    retry_count = int(np.clip(retry_count, 0, 8))

    click_count += retry_count
    if popup_detected:
        click_count += triangular_int(rng, 0, 2)
    if overlay_blocks_action:
        click_count += triangular_int(rng, 1, 3)
    if label == "High" and rng.random() < 0.35:
        click_count += triangular_int(rng, 1, 4)
    click_count = int(np.clip(click_count, 1, 20))

    if label == "Low":
        failed_clicks = int(rng.choice([0, 0, 0, 0, 1]))
        unnecessary_clicks = int(rng.choice([0, 0, 0, 1]))
    elif label == "Medium":
        failed_clicks = int(np.clip(rng.poisson(0.7), 0, 3))
        unnecessary_clicks = int(np.clip(rng.poisson(1.0), 0, 4))
    else:
        failed_clicks = int(np.clip(1 + rng.poisson(1.6), 1, 6))
        unnecessary_clicks = int(np.clip(1 + rng.poisson(2.0), 1, 7))

    if flow == "button_click":
        failed_clicks += 1 if label in {"Medium", "High"} and rng.random() < 0.55 else 0
    if overlay_blocks_action:
        failed_clicks += triangular_int(rng, 1, 3)
        unnecessary_clicks += triangular_int(rng, 1, 3)
    if timeout_occurred:
        unnecessary_clicks += triangular_int(rng, 0, 2)

    failed_clicks = int(np.clip(failed_clicks, 0, 10))
    unnecessary_clicks = int(np.clip(unnecessary_clicks, 0, 12))

    scroll_low, scroll_high = flow_cfg["scroll_range"]
    scroll_count = triangular_int(rng, scroll_low, scroll_high)
    if flow == "search" and label in {"Medium", "High"}:
        scroll_count += triangular_int(rng, 0, 3 if label == "Medium" else 5)
    if flow in {"signup", "form_submit"} and label == "High" and rng.random() < 0.35:
        scroll_count += triangular_int(rng, 1, 3)
    scroll_count = int(np.clip(scroll_count, 0, 10))

    # Error count must be consistent with error flags.
    if label == "Low":
        error_count = int(rng.choice([0, 0, 0, 0, 0, 1]))
    elif label == "Medium":
        error_count = int(np.clip(rng.poisson(0.9), 0, 2))
    else:
        error_count = int(np.clip(1 + rng.poisson(1.6), 1, 5))

    if flow in {"signup", "form_submit"} and label in {"Medium", "High"}:
        error_count += 1 if rng.random() < 0.45 else 0
    if error_message_present:
        error_count = max(error_count, 1)
    if timeout_occurred:
        error_count += 1
    if crash_detected:
        error_count += 1
    if anr_detected:
        error_count += 1
    error_count = int(np.clip(error_count, 0, 7))

    if error_count > 0 and not error_message_present and rng.random() < 0.65:
        error_message_present = 1
        if label == "High":
            error_message_clarity = rng.choice(["medium", "vague"], p=[0.35, 0.65])
        elif label == "Medium":
            error_message_clarity = rng.choice(["clear", "medium", "vague"], p=[0.40, 0.45, 0.15])
        else:
            error_message_clarity = rng.choice(["clear", "medium"], p=[0.75, 0.25])

    path_low, path_high, path_mode = label_cfg["path_range"]
    path_deviation_score = triangular_float(rng, path_low, path_high, path_mode, decimals=3)
    path_deviation_score += float(flow_cfg["path_boost"])
    path_deviation_score += 0.06 if retry_count >= 2 else 0.00
    path_deviation_score += 0.08 if overlay_blocks_action else 0.00
    path_deviation_score += 0.10 if timeout_occurred else 0.00
    path_deviation_score += 0.07 if task_failed else 0.00
    if ambiguity_direction > 0:
        path_deviation_score += 0.05
    elif ambiguity_direction < 0:
        path_deviation_score -= 0.06
    path_deviation_score = round(clamp(path_deviation_score, 0.0, 1.0), 3)

    finish_time_ms = int(round((completion_time * 1000) + rng.normal(0, 150)))
    finish_time_ms = max(finish_time_ms, int(completion_time * 1000 * 0.92))

    return {
        "task_completed": int(task_completed),
        "task_failed": int(task_failed),
        "completion_time": completion_time,
        "click_count": int(click_count),
        "scroll_count": int(scroll_count),
        "keyboard_count": int(keyboard_count),
        "retry_count": int(retry_count),
        "error_count": int(error_count),
        "failed_clicks": int(failed_clicks),
        "unnecessary_clicks": int(unnecessary_clicks),
        "path_deviation_score": path_deviation_score,
        "app_launch_time_ms": int(app_launch_time_ms),
        "screen_load_time_ms": int(screen_load_time_ms),
        "feedback_delay_ms": int(feedback_delay_ms),
        "interaction_response_time_ms": int(interaction_response_time_ms),
        "finish_time_ms": int(finish_time_ms),
        "error_message_present": int(error_message_present),
        "error_message_clarity": str(error_message_clarity),
        "popup_detected": int(popup_detected),
        "overlay_blocks_action": int(overlay_blocks_action),
        "timeout_occurred": int(timeout_occurred),
        "crash_detected": int(crash_detected),
        "anr_detected": int(anr_detected),
    }


# -----------------------------------------------------------------------------
# Report generation
# -----------------------------------------------------------------------------
def build_report(original_df: pd.DataFrame, cleaned_df: pd.DataFrame) -> str:
    original_label_distribution = original_df["friction_level"].value_counts().to_string()
    cleaned_label_distribution = cleaned_df["friction_level"].value_counts().to_string()

    original_scenario_distribution = value_counts_markdown(original_df["scenario_type"])
    cleaned_scenario_distribution = value_counts_markdown(cleaned_df["scenario_type"])

    original_crash_rate = binary_rate(original_df, "crash_detected")
    cleaned_crash_rate = binary_rate(cleaned_df, "crash_detected")
    original_completed_rate = binary_rate(original_df, "task_completed")
    cleaned_completed_rate = binary_rate(cleaned_df, "task_completed")
    original_timeout_rate = binary_rate(original_df, "timeout_occurred")
    cleaned_timeout_rate = binary_rate(cleaned_df, "timeout_occurred")

    metric_by_label = cleaned_df.groupby("friction_level").agg(
        task_completed_rate=("task_completed", "mean"),
        crash_rate=("crash_detected", "mean"),
        timeout_rate=("timeout_occurred", "mean"),
        popup_rate=("popup_detected", "mean"),
        avg_error_count=("error_count", "mean"),
        avg_completion_time=("completion_time", "mean"),
    )

    report = f"""# Android Cleaned Synthetic Dataset Report

## 1. Source Dataset Path

`{INPUT_PATH}`

## 2. Output Dataset Path

`{OUTPUT_PATH}`

## 3. Original Dataset Shape

`{original_df.shape}`

## 4. Cleaned Dataset Shape

`{cleaned_df.shape}`

## 5. Original Label Distribution

```text
{original_label_distribution}
```

## 6. Cleaned Label Distribution

```text
{cleaned_label_distribution}
```

## 7. Original Crash Rate

{fmt_rate(original_crash_rate)}

## 8. Cleaned Crash Rate

{fmt_rate(cleaned_crash_rate)}

## 9. Original Task Completed Rate

{fmt_rate(original_completed_rate)}

## 10. Cleaned Task Completed Rate

{fmt_rate(cleaned_completed_rate)}

## 11. Original Timeout Rate

{fmt_rate(original_timeout_rate)}

## 12. Cleaned Timeout Rate

{fmt_rate(cleaned_timeout_rate)}

## 13. Original Scenario Type Distribution

{original_scenario_distribution}

## 14. Cleaned Scenario Type Distribution

{cleaned_scenario_distribution}

## 15. Cleaned Metric Summary by Label

```text
{metric_by_label.to_string()}
```

## 16. Synthetic Repair Logic

The original Android Appium dataset was audited and found to contain unstable long-run emulator artifacts. Therefore, a cleaned controlled Android dataset was generated using the same dummy Android app flow design, scenario labels, and UX-friction rules. This dataset is used only for the Android Appium experimental extension, while the Web GAgent model remains the main model.

The repair process did not simply replace crashed values with zero. The script regenerated UX metrics using controlled rules based on friction level, scenario type, and flow type. Low-friction rows were regenerated as mostly smooth interactions. Medium-friction rows were regenerated as noticeable but recoverable UX issues. High-friction rows were regenerated as severe UX problems with higher rates of timeout, popup blocking, overlay blocking, failed clicks, retries, vague errors, ANR, and occasional crash events.

The dataset intentionally includes controlled variation and overlap between classes so that the model does not learn an unrealistically perfect pattern.

## 17. Scope Reminder

The Android Appium model is an experimental extension trained on controlled Android dummy-app scenario data. It must not be overclaimed as a fully real-world Android model.

## 18. Main Model Reminder

The Web GAgent model remains the main model for the FYP system.

## 19. Leakage Columns to Exclude During Model Training

The following columns must be excluded from input features during Android model training:

{chr(10).join(f"- `{col}`" for col in LEAKAGE_COLUMNS_TO_EXCLUDE)}

## 20. Documentation Wording

Use this wording:

> The original Android Appium dataset was audited and found to contain unstable long-run emulator artifacts. Therefore, a cleaned controlled Android dataset was generated using the same dummy Android app flow design, scenario labels, and UX-friction rules. This dataset is used only for the Android Appium experimental extension, while the Web GAgent model remains the main model.

Do not claim that all 10,005 Android rows were collected perfectly from stable Appium execution.

Instead claim that the Android dataset was controlled and cleaned using scenario-based UX-friction rules after Appium long-run emulator instability was detected.
"""
    return report


# -----------------------------------------------------------------------------
# Main execution
# -----------------------------------------------------------------------------
def main() -> None:
    if not INPUT_PATH.exists():
        raise FileNotFoundError(f"Input dataset not found: {INPUT_PATH}")

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    REPORT_PATH.parent.mkdir(parents=True, exist_ok=True)

    original_df = pd.read_csv(INPUT_PATH)
    validate_required_columns(original_df)

    original_shape = original_df.shape
    original_columns = list(original_df.columns)

    rng = np.random.default_rng(RANDOM_SEED)
    cleaned_df = original_df.copy(deep=True)

    # Standardize constant columns.
    cleaned_df["target_type"] = "android_application"
    cleaned_df["device_type"] = "android_emulator"
    cleaned_df["platform_name"] = "Android"
    cleaned_df["friction_level_prediction"] = ""

    # Warn if scenario_type and friction_level do not match the expected mapping.
    mismatch_mask = cleaned_df.apply(
        lambda row: SCENARIO_TO_LABEL.get(str(row["scenario_type"]).strip().lower(), row["friction_level"])
        != row["friction_level"],
        axis=1,
    )
    mismatch_count = int(mismatch_mask.sum())
    if mismatch_count > 0:
        print(
            f"WARNING: Found {mismatch_count} rows where scenario_type does not match "
            "the expected good/medium/bad to Low/Medium/High mapping. "
            "friction_level is preserved and used as the repair target."
        )

    generated_metrics = []
    for _, row in cleaned_df.iterrows():
        generated_metrics.append(generate_row_metrics(row, rng))

    generated_metrics_df = pd.DataFrame(generated_metrics, index=cleaned_df.index)
    for column in METRIC_COLUMNS_TO_REGENERATE:
        cleaned_df[column] = generated_metrics_df[column]

    # Preserve original column order and assert no accidental schema drift.
    cleaned_df = cleaned_df[original_columns]

    if cleaned_df.shape[0] != original_shape[0]:
        raise AssertionError("Row count changed during repair. This should never happen.")
    if list(cleaned_df.columns) != original_columns:
        raise AssertionError("Column structure changed during repair. This should never happen.")

    cleaned_df.to_csv(OUTPUT_PATH, index=False)

    report = build_report(original_df, cleaned_df)
    REPORT_PATH.write_text(report, encoding="utf-8")

    # Terminal summary.
    print("\nANDROID CLEANED SYNTHETIC DATASET REPAIR COMPLETE")
    print("=" * 58)
    print(f"Original dataset shape: {original_df.shape}")
    print(f"Cleaned dataset shape:  {cleaned_df.shape}")

    print("\nOriginal friction_level distribution:")
    print(original_df["friction_level"].value_counts().to_string())

    print("\nCleaned friction_level distribution:")
    print(cleaned_df["friction_level"].value_counts().to_string())

    print("\nBefore/After rates:")
    print(f"Original crash rate:         {fmt_rate(binary_rate(original_df, 'crash_detected'))}")
    print(f"Cleaned crash rate:          {fmt_rate(binary_rate(cleaned_df, 'crash_detected'))}")
    print(f"Original task_completed rate:{fmt_rate(binary_rate(original_df, 'task_completed'))}")
    print(f"Cleaned task_completed rate: {fmt_rate(binary_rate(cleaned_df, 'task_completed'))}")
    print(f"Original timeout rate:       {fmt_rate(binary_rate(original_df, 'timeout_occurred'))}")
    print(f"Cleaned timeout rate:        {fmt_rate(binary_rate(cleaned_df, 'timeout_occurred'))}")

    print("\nCleaned metric sanity check by label:")
    sanity = cleaned_df.groupby("friction_level").agg(
        task_completed_rate=("task_completed", "mean"),
        crash_rate=("crash_detected", "mean"),
        timeout_rate=("timeout_occurred", "mean"),
        popup_rate=("popup_detected", "mean"),
        avg_error_count=("error_count", "mean"),
        avg_completion_time=("completion_time", "mean"),
    )
    print(sanity.to_string())

    print(f"\nOutput dataset saved to: {OUTPUT_PATH}")
    print(f"Report saved to:         {REPORT_PATH}")
    print("\nNext step: backup the old dataset, replace it with the cleaned file, then run audit and training.")


if __name__ == "__main__":
    main()
