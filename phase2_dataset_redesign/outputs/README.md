# Outputs

`cleaned_online_common_dataset.csv` is currently a schema placeholder with headers only.

Generate the real Dataset A file by running:

```bash
python phase2_dataset_redesign/scripts/clean_online_common_dataset.py   --input-dir phase2_dataset_preparation/outputs/processed   --output phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv
```

Then validate it:

```bash
python phase2_dataset_redesign/scripts/validate_phase2_schemas.py   --schema phase2_dataset_redesign/schemas/baseline_common_schema.json   --csv phase2_dataset_redesign/outputs/cleaned_online_common_dataset.csv
```
