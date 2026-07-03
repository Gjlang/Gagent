#!/usr/bin/env python3
"""
Clean online/common datasets into Dataset A baseline schema.

Usage example from GAgent root:
    python phase2_dataset_redesign/scripts/clean_online_common_dataset.py \
        --input-dir phase2_dataset_preparation/outputs/processed \
        --output phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv

This script builds Dataset A only. It does not create full GAgent Dataset B attributes.
"""

from __future__ import annotations

import argparse
from pathlib import Path
import numpy as np
import pandas as pd

OUTPUT_COLUMNS = [
    "source_dataset",
    "flow_type",
    "completion_time_ms",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "task_completed",
    "friction_score",
    "friction_level",
]

NUMERIC_COLUMNS = [
    "completion_time_ms",
    "click_count",
    "scroll_count",
    "keyboard_count",
    "retry_count",
    "error_count",
    "failed_clicks",
    "task_completed",
]


def normalise_flow_type(value: object) -> str:
    if pd.isna(value):
        return "unknown_flow"
    text = str(value).strip().lower()
    if not text or text in {"nan", "none", "null", "-1"}:
        return "unknown_flow"
    if text.startswith("http") or ".com" in text or ".org" in text or ".net" in text:
        return "web_interaction"
    if "shop" in text or "product" in text or "cart" in text or "ecommerce" in text:
        return "ecommerce_browsing"
    if "login" in text:
        return "login"
    if "signup" in text or "register" in text:
        return "signup"
    if "search" in text:
        return "search_interaction"
    if "form" in text or "input" in text:
        return "form_interaction"
    allowed = {"web_interaction", "ecommerce_browsing", "form_interaction", "search_interaction", "login", "signup", "checkout", "cta", "unknown_flow"}
    return text if text in allowed else "web_interaction"


def coerce_numeric(series: pd.Series, default: float = 0) -> pd.Series:
    return pd.to_numeric(series, errors="coerce").replace([np.inf, -np.inf], np.nan).fillna(default)


def load_and_standardise(path: Path) -> pd.DataFrame | None:
    try:
        df = pd.read_csv(path)
    except Exception as exc:
        print(f"Skipping unreadable file {path}: {exc}")
        return None

    lower_map = {c.lower(): c for c in df.columns}
    result = pd.DataFrame(index=df.index)

    # Flow type
    if "flow_type" in lower_map:
        result["flow_type"] = df[lower_map["flow_type"]].map(normalise_flow_type)
    else:
        result["flow_type"] = "unknown_flow"

    # Time handling
    if "completion_time_ms" in lower_map:
        result["completion_time_ms"] = coerce_numeric(df[lower_map["completion_time_ms"]])
    elif "completion_time" in lower_map:
        result["completion_time_ms"] = coerce_numeric(df[lower_map["completion_time"]])
    elif "session_length_s" in lower_map:
        result["completion_time_ms"] = coerce_numeric(df[lower_map["session_length_s"]]) * 1000
    else:
        result["completion_time_ms"] = 0

    # Counts
    for col in ["click_count", "scroll_count", "keyboard_count", "retry_count", "error_count", "failed_clicks"]:
        if col in lower_map:
            result[col] = coerce_numeric(df[lower_map[col]]).astype(int)
        else:
            result[col] = 0

    # Task completed
    if "task_completed" in lower_map:
        task = coerce_numeric(df[lower_map["task_completed"]], default=np.nan)
        task = task.where(task.isin([0, 1]), np.nan)
        result["task_completed"] = task
    elif "task_success" in lower_map:
        result["task_completed"] = coerce_numeric(df[lower_map["task_success"]], default=np.nan).clip(0, 1)
    elif "conversion" in lower_map:
        result["task_completed"] = coerce_numeric(df[lower_map["conversion"]], default=np.nan).clip(0, 1)
    else:
        result["task_completed"] = np.nan

    result["source_dataset"] = path.stem

    # Remove rows with no meaningful signal
    signal_sum = (
        result["completion_time_ms"].fillna(0)
        + result["click_count"].fillna(0)
        + result["scroll_count"].fillna(0)
        + result["keyboard_count"].fillna(0)
        + result["retry_count"].fillna(0)
        + result["error_count"].fillna(0)
        + result["failed_clicks"].fillna(0)
    )
    result = result[signal_sum > 0].copy()
    if result.empty:
        return None
    return result


def add_percentile_scores(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df["friction_score"] = 0.0

    grouped = df.groupby("flow_type", dropna=False)
    for col in ["completion_time_ms", "click_count", "scroll_count", "keyboard_count"]:
        p75 = grouped[col].transform(lambda s: s.quantile(0.75) if len(s.dropna()) else np.nan)
        p90 = grouped[col].transform(lambda s: s.quantile(0.90) if len(s.dropna()) else np.nan)
        df.loc[df[col] > p75, "friction_score"] += 1
        df.loc[df[col] > p90, "friction_score"] += 1

    df.loc[df["task_completed"] == 0, "friction_score"] += 4
    df.loc[(df["retry_count"] >= 1) & (df["retry_count"] <= 2), "friction_score"] += 2
    df.loc[df["retry_count"] >= 3, "friction_score"] += 4
    df.loc[df["error_count"] == 1, "friction_score"] += 3
    df.loc[df["error_count"] >= 2, "friction_score"] += 5
    df.loc[df["failed_clicks"] == 1, "friction_score"] += 3
    df.loc[df["failed_clicks"] >= 2, "friction_score"] += 5

    def label(score: float) -> str:
        if score <= 4:
            return "Low"
        if score <= 10:
            return "Medium"
        return "High"

    df["friction_level"] = df["friction_score"].map(label)

    # Override rules
    mask_medium = (df["task_completed"] == 0) & (df["failed_clicks"] >= 1) & (df["friction_level"] == "Low")
    df.loc[mask_medium, "friction_level"] = "Medium"
    mask_high = (df["task_completed"] == 0) & (df["error_count"] >= 2)
    df.loc[mask_high, "friction_level"] = "High"
    return df


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input-dir", required=True, help="Folder containing CSV files to clean.")
    parser.add_argument("--output", default="phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv")
    parser.add_argument("--max-rows", type=int, default=0, help="Optional maximum rows after cleaning. 0 means no limit.")
    args = parser.parse_args()

    input_dir = Path(args.input_dir)
    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)

    frames = []
    for path in sorted(input_dir.rglob("*.csv")):
        name = path.name.lower()
        if any(bad in name for bad in ["validation", "distribution", "summary"]):
            continue
        cleaned = load_and_standardise(path)
        if cleaned is not None and not cleaned.empty:
            frames.append(cleaned)
            print(f"Loaded: {path} -> {len(cleaned)} rows")

    if not frames:
        raise SystemExit("No usable rows found. Check your input directory.")

    df = pd.concat(frames, ignore_index=True)
    df = add_percentile_scores(df)

    # Keep only Dataset A columns
    df = df[OUTPUT_COLUMNS]
    df = df.drop_duplicates()

    if args.max_rows and len(df) > args.max_rows:
        df = df.sample(n=args.max_rows, random_state=42).reset_index(drop=True)

    df.to_csv(output, index=False)
    print(f"Saved Dataset A: {output}")
    print(f"Rows: {len(df)}")
    print(df["friction_level"].value_counts(dropna=False))


if __name__ == "__main__":
    main()
