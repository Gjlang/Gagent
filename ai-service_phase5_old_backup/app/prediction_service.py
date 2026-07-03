from typing import Any, Dict, List, Optional
import numpy as np
import pandas as pd

from app.model_loader import load_model_artifacts
from app.recommendation_service import generate_recommendations
from app.schemas import PredictionInput


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

    return class_probabilities


def predict_single(payload: PredictionInput) -> dict:
    artifacts = load_model_artifacts()

    input_features = payload.model_dump()

    missing_features = [
        feature for feature in artifacts.feature_columns if feature not in input_features
    ]

    if missing_features:
        raise ValueError(f"Missing required features: {missing_features}")

    ordered_input = {
        feature: input_features[feature]
        for feature in artifacts.feature_columns
    }

    input_df = pd.DataFrame([ordered_input], columns=artifacts.feature_columns)

    prediction_input = input_df

    if artifacts.scaler is not None:
        prediction_input = artifacts.scaler.transform(input_df)

    prediction = artifacts.model.predict(prediction_input)
    predicted_value = prediction[0]
    predicted_label = _decode_prediction(predicted_value, artifacts.label_encoder)

    confidence_score: Optional[float] = None
    class_probabilities: Optional[Dict[str, float]] = None

    if hasattr(artifacts.model, "predict_proba"):
        probabilities = artifacts.model.predict_proba(prediction_input)[0]
        class_probabilities = _build_probability_map(
            model=artifacts.model,
            probabilities=probabilities,
            label_encoder=artifacts.label_encoder,
        )
        confidence_score = class_probabilities.get(
            predicted_label,
            round(float(np.max(probabilities)), 4),
        )

    recommendations = generate_recommendations(input_features)

    return {
        "friction_level": predicted_label,
        "confidence_score": confidence_score,
        "model_used": type(artifacts.model).__name__,
        "input_features": input_features,
        "recommendation": recommendations,
        "status": "success",
        "class_probabilities": class_probabilities,
    }


def predict_batch(items: List[PredictionInput]) -> List[dict]:
    results = []

    for item in items:
        results.append(predict_single(item))

    return results