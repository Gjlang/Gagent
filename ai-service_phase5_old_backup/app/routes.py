from fastapi import APIRouter, HTTPException

from app.model_loader import get_model_info, load_model_artifacts
from app.prediction_service import predict_batch, predict_single
from app.schemas import (
    BatchPredictionInput,
    BatchPredictionResponse,
    PredictionInput,
    PredictionResponse,
)


router = APIRouter()


@router.get("/health")
def health_check() -> dict:
    model_loaded = False
    model_error = None

    try:
        load_model_artifacts()
        model_loaded = True
    except Exception as error:
        model_error = str(error)

    return {
        "service": "gagent-ai-service",
        "status": "healthy",
        "model_loaded": model_loaded,
        "model_error": model_error,
    }


@router.get("/model-info")
def model_info() -> dict:
    try:
        return {
            "status": "success",
            **get_model_info(),
        }
    except Exception as error:
        return {
            "status": "error",
            "model_loaded": False,
            "error": str(error),
        }


@router.post("/predict", response_model=PredictionResponse)
def predict(payload: PredictionInput) -> dict:
    try:
        return predict_single(payload)
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"Prediction failed: {error}") from error


@router.post("/batch-predict", response_model=BatchPredictionResponse)
def batch_predict(payload: BatchPredictionInput) -> dict:
    try:
        predictions = predict_batch(payload.items)

        return {
            "predictions": predictions,
            "total_predictions": len(predictions),
            "status": "success",
        }
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"Batch prediction failed: {error}") from error