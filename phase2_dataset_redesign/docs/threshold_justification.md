# Threshold Justification

## Purpose

This document explains the Low, Medium, and High threshold ranges for the final GAgent dataset.

Some thresholds are based on recognised standards. Others must be calibrated from pilot data because they depend on the task flow.

## Threshold table

| Attribute | Low friction | Medium friction | High friction | Source basis | Calibration needed? |
|---|---:|---:|---:|---|---|
| `page_load_time_ms` | ≤2500 | 2501–4000 | >4000 | Core Web Vitals LCP loading threshold used as loading proxy | Yes |
| `dom_content_loaded_ms` | ≤1500 | 1501–3000 | >3000 | Navigation Timing / DOM readiness; task dependent | Yes |
| `time_to_first_byte_ms` | ≤800 | 801–1800 | >1800 | web.dev TTFB guidance | No, but verify locally |
| `feedback_delay_ms` | ≤200 | 201–1000 | >1000 | HCI response-time logic and INP responsiveness threshold | Yes |
| `interaction_to_next_paint_ms` | ≤200 | 201–500 | >500 | Core Web Vitals INP threshold | No |
| `cumulative_layout_shift` | ≤0.10 | 0.11–0.25 | >0.25 | Core Web Vitals CLS threshold | No |
| `completion_time_ms` | ≤ flow p75 of good runs | p75–p90 | >p90 or timeout | Time-on-task is task-specific | Yes |
| `error_count` | 0 | 1 | ≥2 | Error rate usability metric | Yes |
| `failed_clicks` | 0 | 1 | ≥2 | Failed interaction blocks task progress | Yes |
| `retry_count` | 0 | 1–2 | ≥3 | Repeated attempts show recovery effort | Yes |
| `path_deviation_score` | ≤0.20 | 0.21–0.50 | >0.50 | Optimal path / navigation deviation logic | Yes |
| `unnecessary_clicks` | 0 | 1–2 | ≥3 | Extra interaction effort | Yes |

## Notes by attribute

### `page_load_time_ms`

Use Core Web Vitals LCP thresholds as a practical proxy for loading friction:

- Low: page becomes usable around 2.5 seconds or less.
- Medium: page is noticeably slower.
- High: page takes more than 4 seconds and can disrupt task flow.

### `dom_content_loaded_ms`

DOMContentLoaded is not a user-experience metric by itself, but it is useful technical evidence. It must be calibrated because pages with heavy JavaScript may behave differently.

### `time_to_first_byte_ms`

TTFB measures initial server response time. The accepted range from web.dev is:

- good: 0.8 seconds or less
- needs improvement: 0.8 to 1.8 seconds
- poor: above 1.8 seconds

### `feedback_delay_ms`

Feedback delay measures whether the user sees a response after clicking/tapping. It should be collected by measuring time from interaction to visible feedback.

### `interaction_to_next_paint_ms`

INP is a strong responsiveness metric. The threshold is:

- good: 200 ms or less
- needs improvement: 200 to 500 ms
- poor: more than 500 ms

### `cumulative_layout_shift`

CLS measures unexpected layout movement. The threshold is:

- good: 0.10 or less
- needs improvement: 0.10 to 0.25
- poor: more than 0.25

### `completion_time_ms`

Completion time is task-dependent. Use percentile thresholds per `flow_type`, not one fixed threshold for every task.

### `path_deviation_score`

Suggested formula:

```text
path_deviation_score = min(1.0, wrong_steps / expected_steps)
```

Alternative formula:

```text
path_deviation_score = min(1.0, (actual_steps - expected_steps) / expected_steps)
```

Use the same formula consistently.

## Strict warning

Do not claim every threshold is directly from literature. For task-dependent attributes, clearly state that thresholds are calibrated from pilot data.
