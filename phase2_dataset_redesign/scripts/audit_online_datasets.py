#!/usr/bin/env python3
"""
Audit online/public datasets for GAgent Phase 2.

Usage:
    python scripts/audit_online_datasets.py --input-dir ../phase2_dataset_preparation --output docs/generated_online_dataset_audit.csv

This script does not clean or relabel data. It only reports whether each CSV is relevant for Dataset A.
"""

from __future__ import annotations

import argparse
from pathlib import Path
import pandas as pd

RELEVANT_KEYWORDS = {
    "time": ["completion_time", "completion_time_ms", "session_length", "dwell_time"],
    "click": ["click", "click_count"],
    "scroll": ["scroll", "scroll_count"],
    "keyboard": ["keyboard", "key", "keyboard_count"],
    "retry": ["retry", "retry_count"],
    "error": ["error", "error_count", "error_code"],
    "failed_clicks": ["failed_click", "failed_clicks"],
    "task_completed": ["task_completed", "task_success", "conversion"],
    "label": ["friction_level", "frustration", "satisfaction"]
}

REJECT_HINTS = ["review", "googleplay", "google_play", "sentiment", "survey", "questionnaire"]


def find_signal_columns(columns: list[str]) -> dict[str, list[str]]:
    lowered = {c: c.lower() for c in columns}
    result = {}
    for signal, keywords in RELEVANT_KEYWORDS.items():
        matches = [original for original, low in lowered.items() if any(k in low for k in keywords)]
        result[signal] = matches
    return result


def decide_file(path: Path, signals: dict[str, list[str]]) -> tuple[str, str]:
    name = path.name.lower()
    if any(h in name for h in REJECT_HINTS):
        return "Reject", "Likely subjective/text/survey dataset, not automated interaction telemetry."
    usable_signal_count = sum(1 for cols in signals.values() if cols)
    if usable_signal_count >= 5:
        return "Keep for Dataset A", "Contains enough common interaction metrics for baseline mapping."
    if usable_signal_count >= 3:
        return "Use carefully", "Contains partial interaction signals but needs strict cleaning and relabeling."
    return "Reject", "Not enough measurable UX-friction interaction attributes."


def audit_csv(path: Path) -> dict:
    try:
        df_sample = pd.read_csv(path, nrows=1000)
        total_rows = sum(1 for _ in path.open("rb")) - 1
    except Exception as exc:
        return {
            "file": str(path),
            "rows": "ERROR",
            "columns": "ERROR",
            "signals_found": "ERROR",
            "decision": "Reject",
            "reason": f"Could not read CSV: {exc}",
        }

    signals = find_signal_columns(list(df_sample.columns))
    decision, reason = decide_file(path, signals)
    compact_signals = {k: v for k, v in signals.items() if v}
    return {
        "file": str(path),
        "rows": total_rows,
        "columns": " | ".join(df_sample.columns.astype(str)),
        "signals_found": str(compact_signals),
        "decision": decision,
        "reason": reason,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input-dir", required=True, help="Folder containing CSV files to audit.")
    parser.add_argument("--output", default="docs/generated_online_dataset_audit.csv", help="Output CSV path.")
    args = parser.parse_args()

    input_dir = Path(args.input_dir)
    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)

    csv_files = sorted(input_dir.rglob("*.csv"))
    if not csv_files:
        raise SystemExit(f"No CSV files found in {input_dir}")

    rows = [audit_csv(path) for path in csv_files]
    pd.DataFrame(rows).to_csv(output, index=False)
    print(f"Audit completed: {output}")
    print(f"CSV files audited: {len(rows)}")


if __name__ == "__main__":
    main()
