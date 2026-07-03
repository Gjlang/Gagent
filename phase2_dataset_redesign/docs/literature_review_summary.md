# Literature Review Summary for Phase 2 Dataset Redesign

## Purpose

This document explains the academic basis for the redesigned GAgent UX-friction dataset. The dataset should not use random or weak attributes. Each selected attribute must connect to one of the following usability or UX-friction dimensions:

1. Effectiveness: can the user complete the goal?
2. Efficiency: how much time and effort is required?
3. Latency and delay: does the system respond quickly enough?
4. Visual stability and web performance: does the interface load and remain stable?
5. Obstruction and interruption: do popups, banners, or overlays block user action?
6. Error recovery: does the system explain errors clearly enough for recovery?
7. Context of use: what flow, viewport, and scenario is being tested?

## Core academic foundation

### 1. ISO 9241-11:2018

ISO 9241-11 defines usability as the extent to which a system, product, or service can be used by specified users to achieve specified goals with effectiveness, efficiency, and satisfaction in a specified context of use.

For GAgent, this justifies:

- `task_completed`
- `task_failed`
- `completion_time_ms`
- `flow_type`
- `viewport_type`
- `scenario_type`

Use in report: ISO 9241-11 is the main justification for measuring task success, task efficiency, and context variables.

### 2. Usability metrics literature

Recent usability evaluation literature commonly uses task completion, time on task, error rate, and navigation quality as measurable indicators. This supports using automated metrics from Playwright/Appium rather than only surveys.

Justified attributes:

- `task_completed`
- `completion_time_ms`
- `error_count`
- `failed_clicks`
- `retry_count`
- `path_deviation_score`

### 3. Remote and automated usability testing

Remote usability testing research supports collecting interaction logs such as completion time, errors, completion rate, and navigation path. This matches the GAgent idea because Playwright and Appium can act as controlled automated testers.

Justified attributes:

- `click_count`
- `scroll_count`
- `keyboard_count`
- `completion_time_ms`
- `path_deviation_score`
- `task_completed`

### 4. Core Web Vitals and web performance standards

Core Web Vitals define loading performance, responsiveness, and visual stability through metrics such as LCP, INP, and CLS. Since GAgent uses automation on web applications, these metrics are directly relevant.

Justified attributes:

- `page_load_time_ms`
- `interaction_to_next_paint_ms`
- `cumulative_layout_shift`
- `time_to_first_byte_ms`
- `dom_content_loaded_ms`

### 5. Response latency and feedback delay

Human-computer interaction literature shows that users notice delays. Small delays may be acceptable, but long delays disrupt task flow and increase frustration. For GAgent, this supports measuring click-to-feedback delay and page response delay.

Justified attributes:

- `feedback_delay_ms`
- `interaction_to_next_paint_ms`
- `page_load_time_ms`
- `time_to_first_byte_ms`

### 6. Cookie banners, popups, and dark patterns

Cookie banner and dark-pattern research shows that popups, vague wording, consent banners, and misleading overlays can interrupt users and influence behaviour. In GAgent, these are not subjective opinions because they can be detected from the DOM and bounding boxes.

Justified attributes:

- `popup_detected`
- `cookie_banner_detected`
- `overlay_blocks_cta`
- `error_message_clarity`

## APA 7 reference list

Arapakis, I., Park, S., & Pielot, M. (2021). *Impact of response latency on user behaviour in mobile web search*. ACM CHIIR. https://doi.org/10.1145/3406522.3446038

Biselli, T., Kühtreiber, N., & Volkamer, M. (2024). Supporting informed choices about browser cookies. *Proceedings on Privacy Enhancing Technologies, 2024*(1). https://petsymposium.org/popets/2024/popets-2024-0011.php

Generosi, A., Ceccacci, S., Mengoni, M., & Peruzzini, M. (2022). A test management system to support remote usability assessment of web applications. *Information, 13*(10), 505. https://doi.org/10.3390/info13100505

Google. (2025). *Web Vitals*. web.dev. https://web.dev/articles/vitals

Google. (2025). *How the Core Web Vitals metrics thresholds were defined*. web.dev. https://web.dev/articles/defining-core-web-vitals-thresholds

Google. (2025). *Time to First Byte (TTFB)*. web.dev. https://web.dev/articles/ttfb

International Organization for Standardization. (2018). *ISO 9241-11:2018 Ergonomics of human-system interaction — Part 11: Usability: Definitions and concepts*. https://www.iso.org/standard/63500.html

Santos, C., Rossi, A., Sánchez Chamorro, L., Bongard-Blanchy, K., & Abu-Salma, R. (2021). Cookie banners, what's the purpose? Analyzing cookie banner text through a legal lens. *Proceedings of the 20th Workshop on Workshop on Privacy in the Electronic Society*. https://doi.org/10.1145/3463676.3485611

Wronikowska, M. W., Malycha, J., Morgan, L. J., Westgate, V., Petrinic, T., & Young, J. D. (2021). Systematic review of applied usability metrics within usability evaluation methods for hospital electronic healthcare record systems. *Journal of Evaluation in Clinical Practice, 27*(6), 1403–1416. https://doi.org/10.1111/jep.13582
