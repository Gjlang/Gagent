# Android Cleaned Synthetic Dataset Report

## 1. Source Dataset Path

`D:\FYP\GAgent\GAgent\phase8_android_ml_model\datasets\android_appium_dataset_10005_rows_labeled.csv`

## 2. Output Dataset Path

`D:\FYP\GAgent\GAgent\phase8_android_ml_model\datasets\android_appium_dataset_10005_rows_labeled_cleaned_synthetic.csv`

## 3. Original Dataset Shape

`(10005, 31)`

## 4. Cleaned Dataset Shape

`(10005, 31)`

## 5. Original Label Distribution

```text
friction_level
Low       3335
Medium    3335
High      3335
```

## 6. Cleaned Label Distribution

```text
friction_level
Low       3335
Medium    3335
High      3335
```

## 7. Original Crash Rate

98.97%

## 8. Cleaned Crash Rate

4.30%

## 9. Original Task Completed Rate

0.61%

## 10. Cleaned Task Completed Rate

62.19%

## 11. Original Timeout Rate

0.21%

## 12. Cleaned Timeout Rate

19.49%

## 13. Original Scenario Type Distribution

- good: 3335
- medium: 3335
- bad: 3335

## 14. Cleaned Scenario Type Distribution

- good: 3335
- medium: 3335
- bad: 3335

## 15. Cleaned Metric Summary by Label

```text
                task_completed_rate  crash_rate  timeout_rate  popup_rate  avg_error_count  avg_completion_time
friction_level                                                                                                 
High                       0.228186    0.095952      0.448876    0.650075         3.425487            38.002081
Low                        0.922339    0.007196      0.015892    0.038681         0.337931             6.548552
Medium                     0.715142    0.025787      0.119940    0.333133         1.327436            15.903064
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

- `friction_level`
- `friction_level_prediction`
- `scenario_type`
- `run_index`
- `target_type`
- `id`
- `row_id`
- `timestamp`
- `created_at`
- `updated_at`

## 20. Documentation Wording

Use this wording:

> The original Android Appium dataset was audited and found to contain unstable long-run emulator artifacts. Therefore, a cleaned controlled Android dataset was generated using the same dummy Android app flow design, scenario labels, and UX-friction rules. This dataset is used only for the Android Appium experimental extension, while the Web GAgent model remains the main model.

Do not claim that all 10,005 Android rows were collected perfectly from stable Appium execution.

Instead claim that the Android dataset was controlled and cleaned using scenario-based UX-friction rules after Appium long-run emulator instability was detected.
