# Phase 3 Dataset Generation Methodology

## 1. Why a dummy website is used

The dummy website is used because the project needs controlled and repeatable UX friction scenarios. Real websites change frequently and may introduce uncontrolled variables such as advertisements, network issues, third-party scripts, or layout updates. A dummy website allows the same task flow to be tested many times while controlling the specific UX problem being measured.

## 2. Why this is a controlled experimental dataset

The dataset is controlled because each route represents a known UX friction condition. For example, one route simulates a slow page load, another route simulates a blocked CTA, and another route simulates a vague form validation error. The expected friction problem is defined before Playwright runs the task.

## 3. Why this is not random synthetic data

The values are not randomly invented. Playwright opens the website in a real browser, performs interactions, and collects measured values from the browser and DOM. Examples include click count, scroll count, completion time, browser navigation timing, feedback delay, popup detection, overlay detection, and cumulative layout shift.

## 4. Simulated friction scenarios

The dummy website includes slow page load, slow button response, popup blocking, cookie banner blocking, layout shift, hidden CTA, vague error message, timeout, wrong navigation path, form validation error, small button, and disabled or blocked submit scenarios.

## 5. How Playwright collects metrics

Playwright uses browser event listeners to count clicks, keyboard input, and scrolling. It uses the Navigation Timing API for page-load metrics. It uses PerformanceObserver for cumulative layout shift and interaction timing. It checks DOM selectors to detect popups, cookie banners, validation messages, and overlays. It also tracks failed clicks, retries, unnecessary clicks, task completion, and path deviation.

## 6. How labels are generated

The final friction label is generated using weighted friction scoring. Serious problems such as task failure, timeout, blocked CTA, high failed clicks, and severe layout shift receive higher weights. Medium issues such as slow load, popup, retry count, and path deviation receive medium weights. Minor issues such as extra clicks and mild scrolling receive lower weights. The final score is converted into Low, Medium, or High friction.

## 7. Why repeated runs are varied

Repeated runs use controlled variation through seed values, viewport sizes, network conditions, page delay ranges, feedback delay ranges, popup timing, layout shift amount, overlay position, and button size. This prevents duplicate rows while keeping the experiment academically defensible.

## 8. Link to Phase 2 literature mapping

Phase 2 defines the academic attributes, schema, thresholds, and labeling strategy. Phase 3 operationalizes those attributes by generating browser-measured values. This means the dataset connects theoretical UX friction constructs such as effectiveness, efficiency, latency, visual stability, error recovery, and obstruction to measurable Playwright features.
