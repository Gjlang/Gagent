# GAgent Phase 3 Pilot Dataset Audit Report

## 1. Basic Summary
- Dataset path: `D:\FYP\GAgent\GAgent\phase3_web_automation\playwright_agent\outputs\pilot\gagent_phase3_pilot_dataset.csv`
- Total rows: 42
- Total columns: 28
- Duplicate rows: 0
- Missing values: 0

## 2. Schema Check
- Missing columns: None
- Extra columns: None

## 3. Class Distribution
| friction_level | row_count |
| --- | --- |
| Medium | 18 |
| Low | 12 |
| High | 12 |

## 4. Flow Distribution
| flow_type | row_count |
| --- | --- |
| landing_navigation | 12 |
| signup | 12 |
| cta_click | 12 |
| login | 6 |

## 5. Scenario Distribution
| scenario_type | row_count |
| --- | --- |
| layout_shift | 12 |
| popup_blocking | 12 |
| disabled_button_blocked_submit | 6 |
| cookie_banner_blocking | 6 |
| overlay_blocks_cta | 6 |

## 6. Viewport Distribution
| viewport_type | row_count |
| --- | --- |
| desktop | 14 |
| tablet | 14 |
| mobile | 14 |

## 7. Attribute Ranges
| attribute | min | mean | max |
| --- | --- | --- | --- |
| task_completed | 0.0 | 0.714 | 1.0 |
| task_failed | 0.0 | 0.286 | 1.0 |
| completion_time | 2.11 | 8.668 | 16.502 |
| click_count | 0.0 | 1.357 | 2.0 |
| scroll_count | 0.0 | 0.262 | 8.0 |
| keyboard_count | 0.0 | 0.0 | 0.0 |
| retry_count | 0.0 | 0.714 | 1.0 |
| error_count | 0.0 | 0.738 | 3.0 |
| failed_clicks | 0.0 | 0.167 | 1.0 |
| unnecessary_clicks | 0.0 | 0.0 | 0.0 |
| path_deviation_score | 0.0 | 2.619 | 7.0 |
| page_load_time_ms | 319.0 | 989.357 | 1552.0 |
| dom_content_loaded_ms | 318.0 | 988.69 | 1552.0 |
| time_to_first_byte_ms | 308.0 | 961.071 | 1521.0 |
| feedback_delay_ms | 0.0 | 52.286 | 112.0 |
| interaction_to_next_paint_ms | 0.0 | 38.571 | 85.0 |
| cumulative_layout_shift | 0.0 | 0.002 | 0.031 |
| error_message_present | 0.0 | 0.19 | 1.0 |
| error_message_clarity | 0.0 | 0.286 | 1.0 |
| popup_detected | 0.0 | 0.571 | 1.0 |
| cookie_banner_detected | 0.0 | 0.143 | 1.0 |
| overlay_blocks_cta | 0.0 | 0.571 | 1.0 |
| friction_score | 0.0 | 41.738 | 114.0 |

## 8. Issues
- No critical issues found.

## 9. Readiness Decision
Ready for the next Phase 3 step. If this is the final audit, the dataset is ready for Phase 4 model training.