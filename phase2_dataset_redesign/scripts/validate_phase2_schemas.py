#!/usr/bin/env python3
"""
Validate Phase 2 dataset CSV files against JSON schema.

Usage examples:
    python scripts/validate_phase2_schemas.py --schema schemas/baseline_common_schema.json --csv outputs/cleaned_online_common_dataset.csv
    python scripts/validate_phase2_schemas.py --schema schemas/gagent_full_schema.json --csv path/to/gagent_full_dataset.csv
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
import pandas as pd
import numpy as np


def validate_type(series: pd.Series, expected: str) -> list[str]:
    errors = []
    non_null = series.dropna()
    if expected == "string":
        return errors
    if expected == "number":
        converted = pd.to_numeric(non_null, errors="coerce")
        bad = converted.isna().sum()
        if bad:
            errors.append(f"{bad} non-numeric values")
    elif expected == "integer":
        converted = pd.to_numeric(non_null, errors="coerce")
        bad = converted.isna().sum()
        non_integer = ((converted.dropna() % 1) != 0).sum()
        if bad:
            errors.append(f"{bad} non-numeric values")
        if non_integer:
            errors.append(f"{non_integer} non-integer numeric values")
    elif expected == "boolean":
        allowed = {True, False, "true", "false", "True", "False", "0", "1", 0, 1}
        bad = (~non_null.isin(allowed)).sum()
        if bad:
            errors.append(f"{bad} non-boolean values")
    return errors


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--schema", required=True)
    parser.add_argument("--csv", required=True)
    args = parser.parse_args()

    schema_path = Path(args.schema)
    csv_path = Path(args.csv)

    schema = json.loads(schema_path.read_text(encoding="utf-8"))
    df = pd.read_csv(csv_path)

    errors = []
    columns = schema["columns"]
    required = [c["name"] for c in columns if c.get("required", False)]

    missing = [c for c in required if c not in df.columns]
    extra = [c for c in df.columns if c not in [x["name"] for x in columns]]

    if missing:
        errors.append(f"Missing required columns: {missing}")
    if extra:
        errors.append(f"Extra columns not in schema: {extra}")

    for col in columns:
        name = col["name"]
        if name not in df.columns:
            continue
        if col.get("required", False) and df[name].isna().any():
            errors.append(f"Column {name} has {int(df[name].isna().sum())} missing values")
        type_errors = validate_type(df[name], col.get("type", "string"))
        for err in type_errors:
            errors.append(f"Column {name}: {err}")
        if "allowed_values" in col:
            allowed = set(col["allowed_values"])
            values = set(df[name].dropna().unique().tolist())
            unexpected = values - allowed
            if unexpected:
                errors.append(f"Column {name} has unexpected values: {sorted(unexpected)}")
        if "minimum" in col:
            converted = pd.to_numeric(df[name], errors="coerce")
            if (converted.dropna() < col["minimum"]).any():
                errors.append(f"Column {name} has values below minimum {col['minimum']}")
        if "maximum" in col:
            converted = pd.to_numeric(df[name], errors="coerce")
            if (converted.dropna() > col["maximum"]).any():
                errors.append(f"Column {name} has values above maximum {col['maximum']}")

    print(f"Schema: {schema_path}")
    print(f"CSV: {csv_path}")
    print(f"Rows: {len(df)}")
    print(f"Columns: {len(df.columns)}")

    if errors:
        print("\nVALIDATION FAILED")
        for e in errors:
            print(f"- {e}")
        raise SystemExit(1)

    print("\nVALIDATION PASSED")


if __name__ == "__main__":
    main()
