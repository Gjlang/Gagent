# GAgent Phase 3 Final Dataset Audit Report

## 1. Basic Summary
- Dataset path: `D:\FYP\GAgent\GAgent\phase3_web_automation\playwright_agent\outputs\final\gagent_full_ux_friction_dataset.csv`
- Total rows: 10723
- Total columns: 28
- Duplicate rows: 0
- Missing values: 0

## 2. Schema Check
- Missing columns: None
- Extra columns: None

## 3. Class Distribution
| friction_level | row_count |
| --- | --- |
| Medium | 6043 |
| Low | 3598 |
| High | 1082 |

## 4. Flow Distribution
| flow_type | row_count |
| --- | --- |
| landing_navigation | 6615 |
| signup | 4108 |

## 5. Scenario Distribution
| scenario_type | row_count |
| --- | --- |
| layout_shift | 1273 |
| normal_landing | 945 |
| extra_content | 945 |
| hidden_cta | 945 |
| slow_page_load | 945 |
| popup_blocking | 945 |
| wrong_navigation_path | 945 |
| normal_signup | 945 |
| extra_form_fields | 945 |
| hidden_submit_vague_error | 945 |
| vague_error_message | 945 |

## 6. Viewport Distribution
| viewport_type | row_count |
| --- | --- |
| desktop | 3780 |
| tablet | 3478 |
| mobile | 3465 |

## 7. Attribute Ranges
| attribute | min | mean | max |
| --- | --- | --- | --- |
| task_completed | 0.0 | 0.912 | 1.0 |
| task_failed | 0.0 | 0.088 | 1.0 |
| completion_time | 0.888 | 6.446 | 21.095 |
| click_count | 0.0 | 1.471 | 3.0 |
| scroll_count | 0.0 | 0.0 | 0.0 |
| keyboard_count | 0.0 | 0.0 | 0.0 |
| retry_count | 0.0 | 0.647 | 2.0 |
| error_count | 0.0 | 0.471 | 2.0 |
| failed_clicks | 0.0 | 0.441 | 2.0 |
| unnecessary_clicks | 0.0 | 0.264 | 1.0 |
| path_deviation_score | 0.0 | 2.969 | 9.0 |
| page_load_time_ms | 33.0 | 1200.809 | 7504.0 |
| dom_content_loaded_ms | 32.0 | 1199.22 | 7504.0 |
| time_to_first_byte_ms | 22.0 | 1178.485 | 7467.0 |
| feedback_delay_ms | 0.0 | 53.498 | 849.0 |
| interaction_to_next_paint_ms | 0.0 | 42.237 | 817.0 |
| cumulative_layout_shift | 0.0 | 0.0 | 0.016 |
| error_message_present | 0.0 | 0.088 | 1.0 |
| error_message_clarity | -1.0 | 0.441 | 2.0 |
| popup_detected | 0.0 | 0.088 | 1.0 |
| cookie_banner_detected | 0.0 | 0.0 | 0.0 |
| overlay_blocks_cta | 0.0 | 0.088 | 1.0 |
| friction_score | 0.0 | 25.711 | 87.0 |

## 8. Issues
- No critical issues found.

## 9. Readiness Decision
Ready for the next Phase 3 step. If this is the final audit, the dataset is ready for Phase 4 model training.