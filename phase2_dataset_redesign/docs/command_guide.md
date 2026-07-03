# Command Guide

Run these commands from your `GAgent` project root.

## 1. Audit current CSV datasets

```bash
python phase2_dataset_redesign/scripts/audit_online_datasets.py   --input-dir phase2_dataset_preparation   --output phase2_dataset_redesign/docs/generated_online_dataset_audit.csv
```

## 2. Clean Dataset A

```bash
python phase2_dataset_redesign/scripts/clean_online_common_dataset.py   --input-dir phase2_dataset_preparation/outputs/processed   --output phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv
```

Optional limit if the file becomes too large:

```bash
python phase2_dataset_redesign/scripts/clean_online_common_dataset.py   --input-dir phase2_dataset_preparation/outputs/processed   --output phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv   --max-rows 50000
```

## 3. Validate Dataset A

```bash
python phase2_dataset_redesign/scripts/validate_phase2_schemas.py   --schema phase2_dataset_redesign/schemas/baseline_common_schema.json   --csv phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv
```

## 4. Later validate Dataset B

```bash
python phase2_dataset_redesign/scripts/validate_phase2_schemas.py   --schema phase2_dataset_redesign/schemas/gagent_full_schema.json   --csv phase3_web_automation/playwright_agent/outputs/final_export/gagent_full_dataset.csv
```

## Do not run yet

Do not run Phase 4 training until Dataset A and Dataset B schemas are accepted.
