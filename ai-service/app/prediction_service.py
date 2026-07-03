from typing import Any, Dict, List, Optional
import numpy as np
import pandas as pd
from sklearn.pipeline import Pipeline

from app.model_loader import load_model_artifacts
from app.recommendation_service import generate_recommendations


DISPLAY_LABEL_ORDER = ["Low", "Medium", "High"]


def _decode_prediction(predicted_value: Any, label_encoder: Any) -> str:
    if isinstance(predicted_value, str):
        return predicted_value

    if hasattr(label_encoder, "inverse_transform"):
        decoded = label_encoder.inverse_transform([int(predicted_value)])
        return str(decoded[0])

    return str(predicted_value)


def _build_probability_map(
    model: Any,
    probabilities: np.ndarray,
    label_encoder: Any,
) -> Dict[str, float]:
    class_probabilities: Dict[str, float] = {}

    model_classes = getattr(model, "classes_", list(range(len(probabilities))))

    for class_value, probability in zip(model_classes, probabilities):
        class_label = _decode_prediction(class_value, label_encoder)
        class_probabilities[class_label] = round(float(probability), 4)

    ordered_probabilities: Dict[str, float] = {}
    for label in DISPLAY_LABEL_ORDER:
        ordered_probabilities[label] = class_probabilities.get(label, 0.0)

    for label, probability in class_probabilities.items():
        if label not in ordered_probabilities:
            ordered_probabilities[label] = probability

    return ordered_probabilities


def _get_main_feature_order() -> List[str]:
    artifacts = load_model_artifacts()
    main_features = artifacts.feature_columns.get("main_features", [])

    if not main_features:
        raise ValueError("No main_features found inside feature_columns.json.")

    return main_features


def _get_baseline_feature_order() -> List[str]:
    artifacts = load_model_artifacts()

    if artifacts.baseline_metadata:
        baseline_features = artifacts.baseline_metadata.get("baseline_features", [])
        if baseline_features:
            return baseline_features

    if hasattr(artifacts.baseline_model, "feature_names_in_"):
        return [str(feature) for feature in artifacts.baseline_model.feature_names_in_]

    return [
        "completion_time",
        "click_count",
        "scroll_count",
        "keyboard_count",
        "retry_count",
        "error_count",
        "failed_clicks",
        "task_completed",
    ]


def _build_ordered_dataframe(
    input_data: Dict[str, Any],
    feature_order: List[str],
) -> pd.DataFrame:
    missing_features = [
        feature for feature in feature_order if feature not in input_data
    ]

    if missing_features:
        raise ValueError(f"Missing required features: {missing_features}")

    ordered_input = {
        feature: input_data[feature]
        for feature in feature_order
    }

    return pd.DataFrame([ordered_input], columns=feature_order)


def _prepare_main_prediction_input(input_df: pd.DataFrame) -> Any:
    artifacts = load_model_artifacts()

    if isinstance(artifacts.main_model, Pipeline):
        return input_df

    return artifacts.preprocessing_pipeline.transform(input_df)


def _predict(
    model: Any,
    prediction_input: Any,
    label_encoder: Any,
    model_name: str,
    input_features: Dict[str, Any],
) -> Dict[str, Any]:
    prediction = model.predict(prediction_input)
    predicted_value = prediction[0]
    predicted_label = _decode_prediction(predicted_value, label_encoder)

    confidence_score: Optional[float] = None
    class_probabilities: Optional[Dict[str, float]] = None

    if hasattr(model, "predict_proba"):
        probabilities = model.predict_proba(prediction_input)[0]
        class_probabilities = _build_probability_map(
            model=model,
            probabilities=probabilities,
            label_encoder=label_encoder,
        )
        confidence_score = class_probabilities.get(
            predicted_label,
            round(float(np.max(probabilities)), 4),
        )

    recommendations = generate_recommendations(
        input_data=input_features,
        predicted_friction_level=predicted_label,
    )

    return {
        "status": "success",
        "model_name": model_name,
        "friction_level": predicted_label,
        "confidence_score": confidence_score,
        "class_probabilities": class_probabilities,
        "recommendation": recommendations,
        "input_features": input_features,
    }


def predict_gagent(input_data: Dict[str, Any]) -> Dict[str, Any]:
    artifacts = load_model_artifacts()

    feature_order = _get_main_feature_order()
    input_df = _build_ordered_dataframe(input_data, feature_order)
    prediction_input = _prepare_main_prediction_input(input_df)

    return _predict(
        model=artifacts.main_model,
        prediction_input=prediction_input,
        label_encoder=artifacts.label_encoder,
        model_name="main_gagent_model",
        input_features=input_data,
    )


def predict_baseline(input_data: Dict[str, Any]) -> Dict[str, Any]:
    artifacts = load_model_artifacts()

    feature_order = _get_baseline_feature_order()
    input_df = _build_ordered_dataframe(input_data, feature_order)

    return _predict(
        model=artifacts.baseline_model,
        prediction_input=input_df,
        label_encoder=artifacts.label_encoder,
        model_name="baseline_model",
        input_features=input_data,
    )


def batch_predict_gagent(items: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    return [predict_gagent(item) for item in items]


def batch_predict_baseline(items: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    return [predict_baseline(item) for item in items]