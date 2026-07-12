from fastapi import APIRouter, HTTPException
from app.prediction_service import predict_android
from app.schemas import AndroidPredictionInput, AndroidPredictionResponse
from app.model_loader import get_model_info, is_loaded, load_model_artifacts
from app.llm_report_service import (
    check_ollama_available,
    generate_report_explanation,
)
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
    ReportExplanationRequest,
    ReportExplanationResponse,
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
        "ollama_available": (
            check_ollama_available()
        ),
    }


@router.get("/model-info")
def model_info() -> dict:
    try:
        return {
            "status": "success",
            **get_model_info(),
            "llm": {
                "provider": "ollama",
                "available": (
                    check_ollama_available()
                ),
                "default_model": (
                    "llama3.2:1b"
                ),
                "role": (
                    "report explanation only"
                ),
            },
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
    

@router.post("/predict-android", response_model=AndroidPredictionResponse)
def predict_android_endpoint(payload: AndroidPredictionInput):
    try:
        return predict_android(payload.model_dump())
    except FileNotFoundError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    except Exception as error:
        raise HTTPException(
            status_code=500,
            detail=f"Android prediction failed: {error}",
        ) from error


@router.post(
    "/generate-report-explanation",
    response_model=ReportExplanationResponse,
)
def generate_report_explanation_endpoint(
    payload: ReportExplanationRequest,
) -> dict:
    """
    Explain an existing ML prediction.

    This endpoint does not run prediction and does not
    change the supplied Low, Medium, or High result.
    """

    return generate_report_explanation(
        payload.model_dump()
    )