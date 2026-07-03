from fastapi import APIRouter, HTTPException

from app.model_loader import get_model_info, is_loaded, load_model_artifacts
from app.prediction_service import (
    batch_predict_baseline,
    batch_predict_gagent,
    predict_baseline,
    predict_gagent,
)
from app.schemas import (
    BaselinePredictionInput,
    BatchBaselinePredictionInput,
    BatchGAgentPredictionInput,
    BatchPredictionResponse,
    GAgentPredictionInput,
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
        "status": "healthy" if model_loaded else "degraded",
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


@router.post("/predict-gagent", response_model=PredictionResponse)
def predict_gagent_endpoint(payload: GAgentPredictionInput) -> dict:
    try:
        return predict_gagent(payload.model_dump())
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"GAgent prediction failed: {error}") from error


@router.post("/predict-baseline", response_model=PredictionResponse)
def predict_baseline_endpoint(payload: BaselinePredictionInput) -> dict:
    try:
        return predict_baseline(payload.model_dump())
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"Baseline prediction failed: {error}") from error


@router.post("/batch-predict-gagent", response_model=BatchPredictionResponse)
def batch_predict_gagent_endpoint(payload: BatchGAgentPredictionInput) -> dict:
    try:
        predictions = batch_predict_gagent(
            [item.model_dump() for item in payload.items]
        )

        return {
            "status": "success",
            "model_name": "main_gagent_model",
            "total_predictions": len(predictions),
            "predictions": predictions,
        }
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"GAgent batch prediction failed: {error}") from error


@router.post("/batch-predict-baseline", response_model=BatchPredictionResponse)
def batch_predict_baseline_endpoint(payload: BatchBaselinePredictionInput) -> dict:
    try:
        predictions = batch_predict_baseline(
            [item.model_dump() for item in payload.items]
        )

        return {
            "status": "success",
            "model_name": "baseline_model",
            "total_predictions": len(predictions),
            "predictions": predictions,
        }
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(status_code=500, detail=f"Baseline batch prediction failed: {error}") from error