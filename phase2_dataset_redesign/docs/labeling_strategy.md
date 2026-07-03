# Labeling Strategy

## Purpose

This document defines how Low, Medium, and High UX-friction labels should be assigned.

The labels must not be random. They must be based on measurable friction indicators.

---

# Dataset A: Baseline/Common Labeling

Dataset A uses only common attributes that can reasonably exist in online datasets.

## Dataset A attributes used

```text
completion_time_ms
click_count
scroll_count
keyboard_count
retry_count
error_count
failed_clicks
task_completed
```

## Dataset A weighted score

| Attribute | Condition | Score |
|---|---|---:|
| `task_completed` | 0 | +4 |
| `completion_time_ms` | greater than flow p75 | +1 |
| `completion_time_ms` | greater than flow p90 | +2 |
| `click_count` | greater than flow p75 | +1 |
| `click_count` | greater than flow p90 | +2 |
| `scroll_count` | greater than flow p75 | +1 |
| `scroll_count` | greater than flow p90 | +2 |
| `keyboard_count` | greater than flow p75 | +1 |
| `keyboard_count` | greater than flow p90 | +2 |
| `retry_count` | 1–2 | +2 |
| `retry_count` | 3 or more | +4 |
| `error_count` | 1 | +3 |
| `error_count` | 2 or more | +5 |
| `failed_clicks` | 1 | +3 |
| `failed_clicks` | 2 or more | +5 |

## Dataset A thresholds

| Score | Label |
|---:|---|
| 0–4 | Low |
| 5–10 | Medium |
| 11 or above | High |

## Dataset A override rules

```text
If task_completed = 0 and failed_clicks >= 1, label at least Medium.
If task_completed = 0 and error_count >= 2, label High.
```

---

# Dataset B: Main GAgent Labeling

Dataset B uses the full controlled Playwright/Appium attribute set.

## Dataset B weighted score

| Attribute | Condition | Score |
|---|---|---:|
| `task_completed` | 0 | +8 |
| `task_failed` | 1 | +8, but do not double-count with task_completed = 0 |
| fatal timeout | true | +10 |
| `error_count` | 1 | +3 |
| `error_count` | 2 or more | +6 |
| `failed_clicks` | 1 | +4 |
| `failed_clicks` | 2 or more | +7 |
| `retry_count` | 1–2 | +3 |
| `retry_count` | 3 or more | +6 |
| `unnecessary_clicks` | 1–2 | +2 |
| `unnecessary_clicks` | 3 or more | +4 |
| `path_deviation_score` | 0.21–0.50 | +3 |
| `path_deviation_score` | greater than 0.50 | +6 |
| `page_load_time_ms` | 2501–4000 | +2 |
| `page_load_time_ms` | greater than 4000 | +5 |
| `dom_content_loaded_ms` | 1501–3000 | +2 |
| `dom_content_loaded_ms` | greater than 3000 | +4 |
| `time_to_first_byte_ms` | 801–1800 | +2 |
| `time_to_first_byte_ms` | greater than 1800 | +4 |
| `feedback_delay_ms` | 201–1000 | +2 |
| `feedback_delay_ms` | greater than 1000 | +5 |
| `interaction_to_next_paint_ms` | 201–500 | +3 |
| `interaction_to_next_paint_ms` | greater than 500 | +6 |
| `cumulative_layout_shift` | 0.11–0.25 | +3 |
| `cumulative_layout_shift` | greater than 0.25 | +6 |
| `error_message_present` | false while `error_count > 0` | +4 |
| `error_message_clarity` | 1 = partially clear | +2 |
| `error_message_clarity` | 0 = vague | +4 |
| `popup_detected` | true | +3 |
| `cookie_banner_detected` | true | +2 |
| `overlay_blocks_cta` | true | +7 |

## Dataset B thresholds

| Score | Label |
|---:|---|
| 0–10 | Low |
| 11–24 | Medium |
| 25 or above | High |

## Dataset B override rules

```text
If task_failed = 1 and the failure reason is timeout, blocked CTA, repeated failed clicks, or unrecoverable validation error, label High.
If overlay_blocks_cta = true and task_completed = 0, label High.
If failed_clicks >= 2 and retry_count >= 2, label High.
```

## Why weights are not equal

High-weight attributes are direct blockers:

- task failure
- timeout
- blocked CTA
- repeated failed clicks
- unrecoverable errors

Lower-weight attributes are friction signals but not always blockers:

- extra clicks
- extra scrolling
- moderate page delay
- cookie banner visible but not blocking

## Calibration note

After collecting the first pilot Dataset B, check the class distribution. If almost every row becomes Low or High, adjust thresholds. Do not change the feature logic randomly. Only adjust thresholds based on observed pilot data distribution.
