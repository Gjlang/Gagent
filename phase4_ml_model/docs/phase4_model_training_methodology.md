# Phase 4 Model Training Methodology

## 1. Dataset Used

Phase 4 uses the final dataset generated from Phase 3:

`phase4_ml_model/datasets/gagent_full_ux_friction_dataset.csv`

The dataset contains controlled UX friction records generated from automated Playwright web testing. Each row represents a test run with interaction, performance, and UX obstruction attributes.

The target label is:

`friction_level`

The model classifies friction into:

- Low
- Medium
- High

## 2. Why This Dataset Is Stronger Than the Old Dataset

The old Phase 4 dataset used mostly generic interaction metrics. The new Phase 3 dataset is stronger because it includes richer UX friction attributes such as:

- page load time
- DOM content loaded time
- time to first byte
- feedback delay
- interaction to next paint
- cumulative layout shift
- popup detection
- cookie banner detection
- overlay blocking
- path deviation
- unnecessary clicks

This makes the dataset more aligned with the actual purpose of GAgent, which is to detect UX friction from automated mystery shopper testing.

## 3. Feature Groups

The baseline model uses common interaction metrics only:

- completion_time
- click_count
- scroll_count
- keyboard_count
- retry_count
- error_count
- failed_clicks
- task_completed

The main GAgent model uses the full UX-friction feature set:

- flow_type
- viewport_type
- task_completed
- task_failed
- completion_time
- click_count
- scroll_count
- keyboard_count
- retry_count
- error_count
- failed_clicks
- unnecessary_clicks
- path_deviation_score
- page_load_time_ms
- dom_content_loaded_ms
- time_to_first_byte_ms
- feedback_delay_ms
- interaction_to_next_paint_ms
- cumulative_layout_shift
- error_message_present
- error_message_clarity
- popup_detected
- cookie_banner_detected
- overlay_blocks_cta

## 4. Excluded Columns

The following columns are excluded from model training:

- friction_level
- friction_score
- scenario_type
- network_condition
- run_id
- source_dataset
- screenshot_count
- page_url
- route
- expected_friction_level
- screenshot_path
- log_path
- seed
- label_mismatch

`friction_level` is excluded because it is the target label.

`friction_score` is excluded because it directly contributes to the friction label and would cause target leakage.

`scenario_type` is excluded because it can reveal the designed friction scenario too directly.

URLs, routes, IDs, paths, and logging fields are excluded because they are not realistic behavioral ML features and may reduce generalization.

## 5. Baseline Model Purpose

The baseline model exists for comparison. It shows how well a simple model can classify UX friction using common interaction attributes only.

This is useful academically because it proves whether the richer GAgent attributes improve classification performance.

## 6. Main GAgent Model Purpose

The main GAgent model is the final model intended for later Phase 5 FastAPI integration.

It is more suitable for the actual system because it uses realistic UX friction signals from the automated testing pipeline.

## 7. Train/Test Split

The dataset is split using:

- 80% training data
- 20% testing data
- stratified split by friction_level
- random_state = 42

A stratified split is used to preserve the Low, Medium, and High class distribution in both training and testing sets.

## 8. Models Trained

The following models are trained for both baseline and main model experiments:

1. Logistic Regression
2. Decision Tree
3. Random Forest

Random Forest is expected to perform well because the dataset contains nonlinear relationships between interaction behavior and friction severity.

## 9. Evaluation Metrics

The models are evaluated using:

- accuracy
- macro precision
- macro recall
- macro F1-score
- weighted F1-score
- High-class recall
- classification report
- confusion matrix

Macro F1-score is the main selection metric because it treats Low, Medium, and High classes equally.

High-class recall is the second priority because missing severe UX friction is more harmful than misclassifying low friction.

## 10. Best Model Selection Logic

The best model is selected using this priority order:

1. macro F1-score
2. High-class recall
3. weighted F1-score
4. accuracy

Accuracy alone is not used because it can hide poor performance on minority or high-risk classes.

## 11. Limitations

The dataset is controlled and rule-generated. If the model achieves very high accuracy, the result should be interpreted carefully.

High accuracy means the model has learned the controlled friction patterns in the dataset. It does not automatically prove perfect performance on real-world websites or Android applications.

Future work should test the model on external websites and later Android Appium data.

## 12. Phase 5 Readiness

The final selected main model is saved as:

`phase4_ml_model/models/main_gagent_model.pkl`

The model package also includes:

- baseline_model.pkl
- label_encoder.pkl
- preprocessing_pipeline.pkl
- feature_columns.json
- model_metadata.json

These files are required for later Phase 5 FastAPI integration.
