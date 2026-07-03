# Dataset Strategy

## Why GAgent needs two datasets

The old project used a combined dataset that was technically useful but academically weak. The issue is that public datasets usually do not contain full UX-friction attributes such as layout shift, popup blocking, feedback delay, and CTA obstruction.

Therefore, Phase 2 should use two separate datasets.

---

# Dataset A: Baseline/Common Dataset

## Purpose

Dataset A is used to train a simple baseline model using common measurable attributes.

## Source

Dataset A may use:

- processed interaction telemetry data
- processed interaction logs
- e-commerce clickstream/session data
- limited Playwright data with common columns only

## What Dataset A proves

Dataset A proves that a basic model can classify friction using general interaction-effort signals.

## What Dataset A does not prove

Dataset A does not prove the full GAgent contribution because it lacks full UX-friction attributes.

## Dataset A accepted attributes

```text
completion_time_ms
click_count
scroll_count
keyboard_count
retry_count
error_count
failed_clicks
task_completed
friction_level
```

---

# Dataset B: Main GAgent Dataset

## Purpose

Dataset B is the main research dataset. It must be generated from controlled Playwright/Appium scenarios using the final schema.

## Source

Dataset B should come from:

- Phase 3 dummy website scenarios
- controlled Playwright automated web tests
- later Appium Android test scenarios

## What Dataset B proves

Dataset B proves that GAgent can collect measurable UX-friction evidence automatically and classify friction using stronger features.

## Required scenario coverage

Dataset B should include controlled variations of:

1. slow page load
2. slow button response
3. popup blocking
4. cookie banner blocking
5. layout shift
6. hidden CTA
7. vague error message
8. timeout
9. wrong navigation path
10. form validation error
11. overlay blocking CTA

## Recommended class balance

Do not generate only perfect Low-friction runs. Use controlled scenario counts:

| Class | Suggested share |
|---|---:|
| Low | 30%–40% |
| Medium | 30%–40% |
| High | 25%–35% |

Small imbalance is acceptable. Extreme imbalance is not acceptable.

---

# Strict rule

Do not merge Dataset A and Dataset B into one final model without explaining the difference. Dataset A and Dataset B have different attribute coverage and different academic strength.
