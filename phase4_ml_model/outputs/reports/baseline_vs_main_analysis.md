# Baseline vs Main GAgent Model Analysis

## Purpose

This comparison evaluates whether the full GAgent UX friction features improve classification performance compared with a simpler baseline model using common interaction metrics only.

## Best Baseline Model

- Model: Decision Tree
- Accuracy: 0.9965
- Macro F1: 0.9966
- High Recall: 0.9990

## Best Main GAgent Model

- Model: Decision Tree
- Accuracy: 1.0000
- Macro F1: 1.0000
- High Recall: 1.0000

## Interpretation

The main GAgent model achieved equal or better macro F1 performance than the baseline model.
This suggests that the additional UX friction attributes improve or preserve classification quality.

The baseline model is useful for academic comparison because it uses only common attributes such as completion time, click count, retry count, error count, failed clicks, and task completion.

The main GAgent model is more suitable for the actual system because it uses richer UX-specific signals such as page load time, feedback delay, layout shift, popup detection, overlay blocking, and path deviation.

## Important Limitation

If the model performance is extremely high, it should not be overclaimed. The Phase 3 dataset is controlled and rule-generated, so the model may be learning structured friction patterns from the controlled website. Real-world generalization should be tested later using external websites and Android applications.

## Final Decision

The main GAgent model should be used for Phase 5 FastAPI integration because it better represents the actual UX friction detection system.