# EDA Summary

Dataset path: `D:\FYP\GAgent\GAgent\phase4_ml_model\datasets\gagent_full_ux_friction_dataset.csv`
Rows: 30,240
Columns: 28

## Generated Figures

- class_distribution.png
- flow_distribution.png
- viewport_distribution.png
- scenario_distribution.png
- correlation_heatmap.png
- distribution charts for key numeric features
- boxplots by friction level for key numeric features

## Interpretation

The class distribution should be checked to ensure that Low, Medium, and High labels are reasonably balanced.
Flow and viewport charts confirm whether the dataset covers different task types and device layouts.
The correlation heatmap helps identify features that may overlap strongly.
Boxplots show whether UX friction metrics increase as friction level becomes higher.

Important: scenario_type is useful for EDA only and should not be used as an ML feature because it can reveal the designed friction condition.