# Phase 2 Dataset Redesign Package

Project: **GAgent: Autonomous AI-Driven Mystery Shopper System for UX Friction Detection in Web and Android Applications**

This folder is designed to be copied into the root of your `GAgent` project.

Recommended location:

```text
GAgent/phase2_dataset_redesign/
```

Strict order of use:

1. Read `docs/final_dataset_schema.md`.
2. Read `docs/academic_attribute_mapping.md`.
3. Read `docs/labeling_strategy.md`.
4. Run `scripts/audit_online_datasets.py` on your existing Phase 2 dataset folder.
5. Run `scripts/clean_online_common_dataset.py` to create Dataset A.
6. Validate Dataset A using `scripts/validate_phase2_schemas.py`.
7. Do not rebuild Phase 3, Phase 4, FastAPI, or Laravel database fields until the schema is accepted.

Important: `outputs/cleaned_online_common_dataset.csv` is currently a schema placeholder. Generate the real file by running the cleaning script.
