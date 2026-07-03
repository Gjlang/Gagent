from typing import Any, Dict, List, Optional

from pydantic import BaseModel, ConfigDict, Field, field_validator


class PredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    completion_time: float = Field(..., ge=0)
    click_count: int = Field(..., ge=0)
    scroll_count: int = Field(..., ge=-1)
    keyboard_count: int = Field(..., ge=-1)
    retry_count: int = Field(..., ge=0)
    error_count: int = Field(..., ge=0)
    failed_clicks: int = Field(..., ge=0)
    feedback_delay: float = Field(..., ge=-1)
    task_completed: int = Field(...)
    screenshot_count: int = Field(..., ge=0)
    error_message_clarity: int = Field(...)

    @field_validator("task_completed")
    @classmethod
    def validate_task_completed(cls, value: int) -> int:
        allowed_values = {-1, 0, 1}
        if value not in allowed_values:
            raise ValueError("task_completed must be -1, 0, or 1")
        return value

    @field_validator("error_message_clarity")
    @classmethod
    def validate_error_message_clarity(cls, value: int) -> int:
        allowed_values = {-1, 0, 1, 2}
        if value not in allowed_values:
            raise ValueError("error_message_clarity must be -1, 0, 1, or 2")
        return value


class PredictionResponse(BaseModel):
    friction_level: str
    confidence_score: Optional[float]
    model_used: str
    input_features: Dict[str, Any]
    recommendation: List[str]
    status: str
    class_probabilities: Optional[Dict[str, float]] = None


class BatchPredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    items: List[PredictionInput] = Field(..., min_length=1)


class BatchPredictionResponse(BaseModel):
    predictions: List[PredictionResponse]
    total_predictions: int
    status: str