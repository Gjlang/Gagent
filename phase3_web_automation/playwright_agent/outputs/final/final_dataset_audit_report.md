# GAgent Phase 3 Final Dataset Audit Report

## 1. Basic Summary
- Dataset path: `D:\FYP\GAgent\GAgent\phase3_web_automation\playwright_agent\outputs\final\gagent_full_ux_friction_dataset.csv`
- Total rows: 30240
- Total columns: 28
- Duplicate rows: 0
- Missing values: 0

## 2. Schema Check
- Missing columns: None
- Extra columns: None

## 3. Class Distribution
| friction_level | row_count |
| --- | --- |
| Medium | 10683 |
| Low | 9970 |
| High | 9587 |

## 4. Flow Distribution
| flow_type | row_count |
| --- | --- |
| landing_navigation | 6615 |
| cta_click | 6615 |
| signup | 5670 |
| login | 5670 |
| search | 5670 |

## 5. Scenario Distribution
| scenario_type | row_count |
| --- | --- |
| hidden_cta | 1890 |
| layout_shift | 1890 |
| popup_blocking | 1890 |
| timeout | 1890 |
| normal_landing | 945 |
| extra_content | 945 |
| slow_page_load | 945 |
| wrong_navigation_path | 945 |
| normal_signup | 945 |
| extra_form_fields | 945 |
| hidden_submit_vague_error | 945 |
| vague_error_message | 945 |
| disabled_button_blocked_submit | 945 |
| normal_login | 945 |
| wrong_first_attempt | 945 |
| fake_login_button | 945 |
| form_validation_error | 945 |
| normal_search | 945 |
| unclear_search_control | 945 |
| hidden_search_button | 945 |
| slow_button_response | 945 |
| no_result_error | 945 |
| normal_cta | 945 |
| indirect_cta_text | 945 |
| hidden_cta_requires_scroll | 945 |
| cookie_banner_blocking | 945 |
| overlay_blocks_cta | 945 |
| small_click_target | 945 |

## 6. Viewport Distribution
| viewport_type | row_count |
| --- | --- |
| desktop | 10080 |
| tablet | 10080 |
| mobile | 10080 |

## 7. Attribute Ranges
| attribute | min | mean | max |
| --- | --- | --- | --- |
| task_completed | 0.0 | 0.812 | 1.0 |
| task_failed | 0.0 | 0.188 | 1.0 |
| completion_time | 0.888 | 9.757 | 30.714 |
| click_count | 0.0 | 1.86 | 4.0 |
| scroll_count | 0.0 | 0.126 | 16.0 |
| keyboard_count | 0.0 | 0.0 | 0.0 |
| retry_count | 0.0 | 0.833 | 2.0 |
| error_count | 0.0 | 0.577 | 4.0 |
| failed_clicks | 0.0 | 0.468 | 2.0 |
| unnecessary_clicks | 0.0 | 0.406 | 2.0 |
| path_deviation_score | 0.0 | 3.712 | 10.0 |
| page_load_time_ms | 33.0 | 1118.022 | 7504.0 |
| dom_content_loaded_ms | 31.0 | 1116.3 | 7504.0 |
| time_to_first_byte_ms | 20.0 | 1095.618 | 7467.0 |
| feedback_delay_ms | 0.0 | 54.829 | 849.0 |
| interaction_to_next_paint_ms | 0.0 | 43.306 | 817.0 |
| cumulative_layout_shift | 0.0 | 0.0 | 0.039 |
| error_message_present | 0.0 | 0.11 | 1.0 |
| error_message_clarity | -1.0 | 0.469 | 2.0 |
| popup_detected | 0.0 | 0.125 | 1.0 |
| cookie_banner_detected | 0.0 | 0.031 | 1.0 |
| overlay_blocks_cta | 0.0 | 0.125 | 1.0 |
| friction_score | 0.0 | 34.886 | 145.0 |

## 8. Issues
- No critical issues found.

## 9. Readiness Decision
Ready for the next Phase 3 step. If this is the final audit, the dataset is ready for Phase 4 model training.