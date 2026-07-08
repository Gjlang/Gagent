# Android Appium Dataset Audit Report

Dataset path: `D:\FYP\GAgent\GAgent\phase8_android_ml_model\datasets\android_appium_dataset_10005_rows_labeled.csv`
Shape: **10005 rows × 31 columns**
Ready for training: **YES**

## Column List

- `target_type`
- `flow_type`
- `scenario_type`
- `device_type`
- `platform_name`
- `task_completed`
- `task_failed`
- `completion_time`
- `click_count`
- `scroll_count`
- `keyboard_count`
- `retry_count`
- `error_count`
- `failed_clicks`
- `unnecessary_clicks`
- `path_deviation_score`
- `app_launch_time_ms`
- `screen_load_time_ms`
- `feedback_delay_ms`
- `interaction_response_time_ms`
- `finish_time_ms`
- `error_message_present`
- `error_message_clarity`
- `popup_detected`
- `overlay_blocks_action`
- `timeout_occurred`
- `crash_detected`
- `anr_detected`
- `friction_level_prediction`
- `run_index`
- `friction_level`

## Missing Values

| Value | Count |
|---|---:|
| friction_level_prediction | 10005 |

## Duplicate Rows

Duplicate row count: **0**

## Constant Columns

- `target_type`
- `device_type`
- `platform_name`
- `friction_level_prediction`

## friction_level Distribution

| Value | Count |
|---|---:|
| Low | 3335 |
| Medium | 3335 |
| High | 3335 |

## scenario_type Distribution

| Value | Count |
|---|---:|
| good | 3335 |
| medium | 3335 |
| bad | 3335 |

## flow_type Distribution

| Value | Count |
|---|---:|
| login | 2001 |
| signup | 2001 |
| search | 2001 |
| button_click | 2001 |
| form_submit | 2001 |

## Quality Signals

- crash_detected rate: **4.30%**
- task_completed rate: **62.19%**

### crash_detected by run_index summary

|       |   crash_detected |
|:------|-----------------:|
| count |         667      |
| mean  |           0.043  |
| std   |           0.0499 |
| min   |           0      |
| 25%   |           0      |
| 50%   |           0.0667 |
| 75%   |           0.0667 |
| max   |           0.2667 |

## Numeric Column Summary

|                              |   count |       mean |        std |      min |      25% |      50% |       75% |   max |
|:-----------------------------|--------:|-----------:|-----------:|---------:|---------:|---------:|----------:|------:|
| task_completed               |   10005 |     0.6219 |     0.4849 |    0     |    0     |     1    |     1     |     1 |
| task_failed                  |   10005 |     0.3781 |     0.4849 |    0     |    0     |     0    |     1     |     1 |
| completion_time              |   10005 |    20.1512 |    14.7509 |    2.64  |    7.4   |    14.9  |    31.33  |    65 |
| click_count                  |   10005 |     8.1922 |     4.231  |    1     |    5     |     7    |    11     |    20 |
| scroll_count                 |   10005 |     1.5333 |     1.4643 |    0     |    1     |     1    |     2     |     8 |
| keyboard_count               |   10005 |     2.4789 |     1.6836 |    0     |    2     |     2    |     4     |     6 |
| retry_count                  |   10005 |     2.3546 |     2.455  |    0     |    0     |     2    |     4     |     8 |
| error_count                  |   10005 |     1.697  |     1.6248 |    0     |    0     |     1    |     3     |     7 |
| failed_clicks                |   10005 |     1.76   |     1.958  |    0     |    0     |     1    |     3     |    10 |
| unnecessary_clicks           |   10005 |     2.1868 |     2.3518 |    0     |    0     |     1    |     4     |    12 |
| path_deviation_score         |   10005 |     0.4959 |     0.3322 |    0.002 |    0.167 |     0.45 |     0.828 |     1 |
| app_launch_time_ms           |   10005 |  1609.48   |   895.062  |  460     |  916     |  1329    |  2110     |  5912 |
| screen_load_time_ms          |   10005 |  2769.76   |  2346.75   |  303     |  721     |  1763    |  4578     | 10629 |
| feedback_delay_ms            |   10005 |  3105.01   |  3094.98   |  106     |  459     |  1606    |  5296     | 14878 |
| interaction_response_time_ms |   10005 |  3577.9    |  3491.98   |  120     |  577     |  2034    |  6157     | 16479 |
| finish_time_ms               |   10005 | 20147.5    | 14749      | 2484     | 7414     | 14931    | 31312     | 65203 |
| error_message_present        |   10005 |     0.6518 |     0.4764 |    0     |    0     |     1    |     1     |     1 |
| popup_detected               |   10005 |     0.3406 |     0.4739 |    0     |    0     |     0    |     1     |     1 |
| overlay_blocks_action        |   10005 |     0.2268 |     0.4188 |    0     |    0     |     0    |     0     |     1 |
| timeout_occurred             |   10005 |     0.1949 |     0.3961 |    0     |    0     |     0    |     0     |     1 |
| crash_detected               |   10005 |     0.043  |     0.2028 |    0     |    0     |     0    |     0     |     1 |
| anr_detected                 |   10005 |     0.0747 |     0.2629 |    0     |    0     |     0    |     0     |     1 |
| friction_level_prediction    |       0 |   nan      |   nan      |  nan     |  nan     |   nan    |   nan     |   nan |
| run_index                    |   10005 |   334      |   192.556  |    1     |  167     |   334    |   501     |   667 |

## Categorical Columns

- `target_type`: 1 unique values
- `flow_type`: 5 unique values
- `scenario_type`: 3 unique values
- `device_type`: 1 unique values
- `platform_name`: 1 unique values
- `error_message_clarity`: 4 unique values
- `friction_level`: 3 unique values

## Leakage Columns to Exclude

- `scenario_type`
- `friction_level_prediction`
- `run_index`
- `friction_level`

## friction_level_prediction Check

friction_level_prediction exists and is fully empty. Correct: exclude it from training.

## Invalid Value Checks

- No invalid values detected.

## Serious Quality Issues

- No serious quality issue detected.

## Training Decision

The dataset is ready for training.
Do not use `scenario_type`, `friction_level`, or `friction_level_prediction` as model input features.