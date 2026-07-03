# Dataset Audit: gagent_full

Rows: 30,240
Columns: 28
Duplicate rows: 0
Total missing values: 0

## Column Names

- flow_type
- scenario_type
- viewport_type
- network_condition
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
- friction_score
- friction_level

## Data Types

- flow_type: str
- scenario_type: str
- viewport_type: str
- network_condition: str
- task_completed: int64
- task_failed: int64
- completion_time: float64
- click_count: int64
- scroll_count: int64
- keyboard_count: int64
- retry_count: int64
- error_count: int64
- failed_clicks: int64
- unnecessary_clicks: int64
- path_deviation_score: int64
- page_load_time_ms: int64
- dom_content_loaded_ms: int64
- time_to_first_byte_ms: int64
- feedback_delay_ms: int64
- interaction_to_next_paint_ms: int64
- cumulative_layout_shift: float64
- error_message_present: int64
- error_message_clarity: int64
- popup_detected: int64
- cookie_banner_detected: int64
- overlay_blocks_cta: int64
- friction_score: int64
- friction_level: str

## Class Distribution

- Medium: 10,683 (35.33%)
- Low: 9,970 (32.97%)
- High: 9,587 (31.70%)

## Flow Distribution

- landing_navigation: 6,615 (21.88%)
- cta_click: 6,615 (21.88%)
- signup: 5,670 (18.75%)
- login: 5,670 (18.75%)
- search: 5,670 (18.75%)

## Viewport Distribution

- desktop: 10,080 (33.33%)
- tablet: 10,080 (33.33%)
- mobile: 10,080 (33.33%)

## Scenario Distribution

- hidden_cta: 1,890 (6.25%)
- layout_shift: 1,890 (6.25%)
- popup_blocking: 1,890 (6.25%)
- timeout: 1,890 (6.25%)
- normal_landing: 945 (3.12%)
- extra_content: 945 (3.12%)
- slow_page_load: 945 (3.12%)
- wrong_navigation_path: 945 (3.12%)
- normal_signup: 945 (3.12%)
- extra_form_fields: 945 (3.12%)
- hidden_submit_vague_error: 945 (3.12%)
- vague_error_message: 945 (3.12%)
- disabled_button_blocked_submit: 945 (3.12%)
- normal_login: 945 (3.12%)
- wrong_first_attempt: 945 (3.12%)
- fake_login_button: 945 (3.12%)
- form_validation_error: 945 (3.12%)
- normal_search: 945 (3.12%)
- unclear_search_control: 945 (3.12%)
- hidden_search_button: 945 (3.12%)
- slow_button_response: 945 (3.12%)
- no_result_error: 945 (3.12%)
- normal_cta: 945 (3.12%)
- indirect_cta_text: 945 (3.12%)
- hidden_cta_requires_scroll: 945 (3.12%)
- cookie_banner_blocking: 945 (3.12%)
- overlay_blocks_cta: 945 (3.12%)
- small_click_target: 945 (3.12%)

## Numeric Summary

Numeric columns: 23
Saved to: outputs/audit/gagent_full_numeric_summary.csv

## Outlier Check

- task_completed: 5,670 possible outliers
- task_failed: 5,670 possible outliers
- completion_time: 148 possible outliers
- click_count: 2,835 possible outliers
- scroll_count: 491 possible outliers
- keyboard_count: 0 possible outliers
- retry_count: 0 possible outliers
- error_count: 938 possible outliers
- failed_clicks: 0 possible outliers
- unnecessary_clicks: 7,560 possible outliers
- path_deviation_score: 0 possible outliers
- page_load_time_ms: 1,105 possible outliers
- dom_content_loaded_ms: 1,112 possible outliers
- time_to_first_byte_ms: 1,099 possible outliers
- feedback_delay_ms: 3 possible outliers
- interaction_to_next_paint_ms: 5 possible outliers
- cumulative_layout_shift: 1,150 possible outliers
- error_message_present: 3,322 possible outliers
- error_message_clarity: 0 possible outliers
- popup_detected: 3,780 possible outliers
- cookie_banner_detected: 945 possible outliers
- overlay_blocks_cta: 3,780 possible outliers
- friction_score: 480 possible outliers

## Leakage Check

- Exclude `friction_level` from ML features.
- Exclude `friction_score` from ML features.
- Exclude `scenario_type` from ML features.

## Feature Reality Check

Safe main features found: 24
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

## Critical Issues

No critical issues found.


---

# Dataset Audit: gagent_common

Rows: 30,240
Columns: 9
Duplicate rows: 4,589
Total missing values: 0

## Column Names

- completion_time
- click_count
- scroll_count
- keyboard_count
- retry_count
- error_count
- failed_clicks
- task_completed
- friction_level

## Data Types

- completion_time: float64
- click_count: int64
- scroll_count: int64
- keyboard_count: int64
- retry_count: int64
- error_count: int64
- failed_clicks: int64
- task_completed: int64
- friction_level: str

## Class Distribution

- Medium: 10,683 (35.33%)
- Low: 9,970 (32.97%)
- High: 9,587 (31.70%)

## Numeric Summary

Numeric columns: 8
Saved to: outputs/audit/gagent_common_numeric_summary.csv

## Outlier Check

- completion_time: 148 possible outliers
- click_count: 2,835 possible outliers
- scroll_count: 491 possible outliers
- keyboard_count: 0 possible outliers
- retry_count: 0 possible outliers
- error_count: 938 possible outliers
- failed_clicks: 0 possible outliers
- task_completed: 5,670 possible outliers

## Leakage Check

- Exclude `friction_level` from ML features.

## Feature Reality Check

Safe main features found: 8
- task_completed
- completion_time
- click_count
- scroll_count
- keyboard_count
- retry_count
- error_count
- failed_clicks

Missing expected main features:
- flow_type
- viewport_type
- task_failed
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

## Critical Issues

No critical issues found.
