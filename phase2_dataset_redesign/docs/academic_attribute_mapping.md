# Academic Attribute Mapping

## Rule for keeping attributes

An attribute should be kept only if it satisfies all four conditions:

1. It is connected to UX friction.
2. It is supported by academic literature, a recognised standard, or official technical documentation.
3. It can be collected automatically by Playwright or Appium.
4. It has a clear role in the dataset: metadata, ML input, target label, or labeling helper.

## Mapping table

| Attribute | UX concept | Academic / technical support | Measurement method | Role | Dataset |
|---|---|---|---|---|---|
| `source_dataset` | Data provenance | Good research practice | Added during ETL | Metadata only | A, B |
| `run_id` | Traceability | Experiment reproducibility | Generated per automated test run | Metadata only | B |
| `flow_type` | Context of use | ISO 9241-11 context of use | Assign from scenario type, not raw URL | Metadata / optional feature | A, B |
| `viewport_type` | Context of use | ISO 9241-11 context of use | Playwright viewport or Appium device profile | Metadata / optional feature | B |
| `scenario_type` | Controlled test condition | Experimental design | Route/scenario config | Metadata only | B |
| `task_completed` | Effectiveness | ISO 9241-11; usability metrics literature | Success selector, target URL, or confirmation text | ML input | A, B |
| `task_failed` | Effectiveness failure | ISO 9241-11; error-rate literature | Timeout, unreachable goal, blocked CTA | Labeling helper / diagnostic | B |
| `completion_time_ms` | Efficiency | ISO 9241-11; time-on-task literature | End timestamp minus start timestamp | ML input | A, B |
| `click_count` | Interaction effort | Remote usability testing metrics | Count click/tap events | ML input | A, B |
| `scroll_count` | Interaction effort | Remote usability testing metrics | Count scroll/wheel/swipe events | ML input | A, B |
| `keyboard_count` | Input effort | Remote usability testing metrics | Count fill/send-key events | ML input | A, B |
| `retry_count` | Recovery effort | Error recovery / task efficiency | Count repeated attempt on same target/action | ML input | A, B |
| `error_count` | Error frequency | Usability metrics literature | Detect validation messages, HTTP failures, exception states | ML input | A, B |
| `failed_clicks` | Failed interaction | Interaction usability / error metrics | Click target not found, no response, disabled target | ML input | A, B |
| `unnecessary_clicks` | Inefficient action | Efficiency / task path literature | Actual clicks minus expected clicks | ML input | B |
| `path_deviation_score` | Navigation deviation | Remote usability testing and optimal navigation | Compare actual route/actions with ideal path | ML input | B |
| `page_load_time_ms` | Loading delay | Core Web Vitals LCP loading threshold as proxy | Navigation Timing API / load event | ML input | B |
| `dom_content_loaded_ms` | DOM readiness | Navigation Timing API | `domContentLoadedEventEnd - startTime` | ML input | B |
| `time_to_first_byte_ms` | Server response delay | web.dev TTFB guidance | `responseStart - requestStart` | ML input | B |
| `feedback_delay_ms` | System feedback delay | HCI response-time literature / INP logic | Click timestamp to visible UI feedback | ML input | A optional, B required |
| `interaction_to_next_paint_ms` | Interaction responsiveness | Core Web Vitals INP | PerformanceObserver / web-vitals | ML input | B |
| `cumulative_layout_shift` | Visual instability | Core Web Vitals CLS | PerformanceObserver layout-shift entries | ML input | B |
| `error_message_present` | Error recovery | Error recovery and usability feedback | Detect visible error container/text | ML input | B |
| `error_message_clarity` | Error recovery quality | Error-message clarity and dark-pattern wording literature | Rule-based clarity scoring from visible text or controlled scenario label | ML input | B |
| `popup_detected` | Obstruction/interruption | Cookie banner and popup usability studies | Detect modal/dialog/overlay selectors | ML input | B |
| `cookie_banner_detected` | Consent interruption | Cookie-banner research | Detect cookie text/selectors | ML input | B |
| `overlay_blocks_cta` | Direct task obstruction | UX obstruction / task failure logic | Bounding-box overlap between overlay and CTA | ML input | B |
| `friction_score` | Labeling calculation | Transparent rule-based labeling | Weighted formula | Labeling helper only | A, B |
| `friction_level` | Target class | Derived from scoring rule | Low/Medium/High thresholds | Target label | A, B |

## Attributes removed from ML input

| Attribute | Decision | Reason |
|---|---|---|
| `screenshot_count` | Remove from ML input | It is evidence collection, not a cause of friction. |
| `page_url` | Remove from ML input | Raw URLs cause memorisation and poor generalisation. |
| `friction_scenario` | Rename to `scenario_type`; metadata only | Scenario name should not leak the answer into the model. |
| `source_dataset` | Metadata only | Source identity should not become a model shortcut. |
| `friction_score` | Labeling helper only | The model must not train on the formula used to create the target. |

## Strict note

Do not add attributes simply because they look useful. If Playwright/Appium cannot collect the attribute automatically, it should not be part of the main GAgent dataset.
