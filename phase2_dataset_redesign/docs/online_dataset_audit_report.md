# Online Dataset Audit Report

## Purpose

This report decides which current online/public datasets can still be used in Phase 2.

The rule is strict: do not force unrelated columns into UX-friction attributes. If an online dataset does not contain a metric, do not invent it.

## Audit summary from current project

The current processed Phase 2 files mainly contain this old schema:

```text
source_dataset
flow_type
completion_time
click_count
scroll_count
keyboard_count
retry_count
error_count
failed_clicks
feedback_delay
task_completed
screenshot_count
error_message_clarity
friction_level
```

This schema is usable only as a starting point for Dataset A. It is not enough for the main GAgent dataset.

## Dataset decision table

| Dataset file | Useful columns | Weak columns | Mapped GAgent attributes | Keep / Reject | Reason |
|---|---|---|---|---|---|
| `processed_interaction_telemetry_large.csv` | completion time, click/scroll/keyboard, errors, task completion | lacks full UX-performance metrics | Dataset A common attributes | Keep | Most useful for baseline because it contains interaction and error signals. |
| `processed_interactions.csv` | click, scroll, keyboard, time proxies | raw/weak flow labels, unclear task completion | Dataset A common attributes | Keep carefully | Good for behaviour proxies but weak for final labels. |
| `processed_eshop_clothing_2008.csv` | e-commerce browsing/session activity | weak error and completion meaning | Dataset A common attributes only | Keep carefully | Can support baseline e-commerce interaction patterns. |
| `phase2_unique_ux_dataset.csv` | old combined common schema | many weak labels/proxy values | Reference only | Do not use as final | Useful to compare with old work, but should not be final Phase 2 output. |
| `phase2_unique_balanced_ux_dataset.csv` | class-balanced old schema | inherits weak labels | Reference only | Do not use as final | Balanced does not mean academically valid. |
| Text review datasets | review text, sentiment, rating | not automated interaction telemetry | none | Reject for ML | Not measurable by Playwright/Appium in current scope. |
| Survey datasets | satisfaction, SUS, perception | subjective, not automated telemetry | documentation only | Reject for ML | Useful in literature discussion, not training data. |
| UI perception datasets | subjective UI ratings | not event-level telemetry | documentation only | Reject for ML | Cannot be converted into automated UX-friction evidence safely. |

## Final online datasets to use

Use for Dataset A only:

```text
processed_interaction_telemetry_large.csv
processed_interactions.csv
processed_eshop_clothing_2008.csv
```

Use as reference only:

```text
phase2_unique_ux_dataset.csv
phase2_unique_balanced_ux_dataset.csv
```

Reject from ML training:

```text
Google Play review datasets
review text datasets
survey-only datasets
UI perception datasets
```

## Cleaning rules

1. Rename `completion_time` to `completion_time_ms`.
2. Rename `feedback_delay` to `feedback_delay_ms` only if retained for analysis.
3. Remove `screenshot_count` from ML features.
4. Do not use `source_dataset` as ML input.
5. Do not use raw URLs as `flow_type`.
6. Convert missing values such as `-1` to null where they mean unknown.
7. Keep only common Dataset A features.
8. Recalculate `friction_score` and `friction_level` using the new baseline formula.
9. Drop rows where there is no usable interaction signal.
10. Keep a clear audit log before training.

## Limitation warning

Dataset A is acceptable only as a baseline dataset. It should not be presented as the main GAgent dataset because it does not include full measurable UX-friction factors.
