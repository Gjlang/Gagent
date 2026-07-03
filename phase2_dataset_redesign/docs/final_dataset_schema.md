# Final Dataset Schema

## Purpose

This file freezes the Phase 2 dataset schema before Phase 3 is rebuilt.

GAgent uses two datasets:

1. Dataset A: Baseline/Common Dataset
2. Dataset B: Main GAgent Dataset

Dataset A is for baseline model comparison. Dataset B is the main FYP contribution.

---

# Dataset A: Baseline/Common Dataset

## Purpose

Dataset A uses online/public datasets and any Playwright data that only contains common attributes. It should not pretend to include full UX-friction metrics when those metrics are missing.

## Target size

Recommended: around 50,000 rows if clean and defensible.

## Dataset A columns

```text
source_dataset
flow_type
completion_time_ms
click_count
scroll_count
keyboard_count
retry_count
error_count
failed_clicks
task_completed
friction_score
friction_level
```

## Dataset A feature usage

| Column | Role | Use in ML? |
|---|---|---|
| `source_dataset` | Metadata | No |
| `flow_type` | Metadata / optional context | Usually no for baseline |
| `completion_time_ms` | ML input | Yes |
| `click_count` | ML input | Yes |
| `scroll_count` | ML input | Yes |
| `keyboard_count` | ML input | Yes |
| `retry_count` | ML input | Yes |
| `error_count` | ML input | Yes |
| `failed_clicks` | ML input | Yes |
| `task_completed` | ML input if real | Yes |
| `friction_score` | Labeling helper | No |
| `friction_level` | Target label | Target only |

## Dataset A warning

Dataset A must not include fake values for:

- `page_load_time_ms`
- `dom_content_loaded_ms`
- `time_to_first_byte_ms`
- `interaction_to_next_paint_ms`
- `cumulative_layout_shift`
- `popup_detected`
- `cookie_banner_detected`
- `overlay_blocks_cta`

If the public dataset does not contain the metric, leave it out of Dataset A.

---

# Dataset B: Main GAgent Dataset

## Purpose

Dataset B is the proper GAgent dataset collected from controlled Playwright and later Appium test scenarios. This is the dataset that should support the final contribution of the FYP.

## Target size

Recommended: 10,000 to 30,000 rows minimum. More is acceptable if generated through controlled scenario variation, not duplicate repetition.

## Dataset B columns

```text
run_id
source_dataset
flow_type
viewport_type
scenario_type
task_completed
task_failed
completion_time_ms
click_count
scroll_count
keyboard_count
retry_count
error_count
failed_clicks
unnecessary_clicks
path_deviation_score
page_load_time_ms
dom_content_loaded_ms
time_to_first_byte_ms
feedback_delay_ms
interaction_to_next_paint_ms
cumulative_layout_shift
error_message_present
error_message_clarity
popup_detected
cookie_banner_detected
overlay_blocks_cta
friction_score
friction_level
```

## Dataset B feature usage

| Column | Role | Use in ML? |
|---|---|---|
| `run_id` | Metadata | No |
| `source_dataset` | Metadata | No |
| `flow_type` | Context | Optional |
| `viewport_type` | Context | Optional |
| `scenario_type` | Metadata | No, avoid answer leakage |
| `task_completed` | ML input | Yes |
| `task_failed` | Diagnostic / optional input | Use carefully to avoid duplicate with `task_completed` |
| `completion_time_ms` | ML input | Yes |
| `click_count` | ML input | Yes |
| `scroll_count` | ML input | Yes |
| `keyboard_count` | ML input | Yes |
| `retry_count` | ML input | Yes |
| `error_count` | ML input | Yes |
| `failed_clicks` | ML input | Yes |
| `unnecessary_clicks` | ML input | Yes |
| `path_deviation_score` | ML input | Yes |
| `page_load_time_ms` | ML input | Yes |
| `dom_content_loaded_ms` | ML input | Yes |
| `time_to_first_byte_ms` | ML input | Yes |
| `feedback_delay_ms` | ML input | Yes |
| `interaction_to_next_paint_ms` | ML input | Yes |
| `cumulative_layout_shift` | ML input | Yes |
| `error_message_present` | ML input | Yes |
| `error_message_clarity` | ML input | Yes |
| `popup_detected` | ML input | Yes |
| `cookie_banner_detected` | ML input | Yes |
| `overlay_blocks_cta` | ML input | Yes |
| `friction_score` | Labeling helper | No |
| `friction_level` | Target label | Target only |

## Final decision

Freeze this schema before modifying:

- `phase3_web_automation/`
- `phase4_ml_model/`
- `ai-service/`
- Laravel migrations
- Laravel dashboard views
