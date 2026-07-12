from typing import Any, Dict, List, Optional
from typing import Dict, List, Optional
from pydantic import BaseModel, Field, ConfigDict, field_validator

from pydantic import BaseModel, ConfigDict, Field, field_validator


class GAgentPredictionInput(BaseModel):
    model_config = ConfigDict(
        extra="forbid",
        json_schema_extra={
            "examples": [
                {
                    "flow_type": "landing_navigation",
                    "viewport_type": "desktop",
                    "task_completed": 1,
                    "task_failed": 0,
                    "completion_time": 2.5,
                    "click_count": 2,
                    "scroll_count": 1,
                    "keyboard_count": 0,
                    "retry_count": 0,
                    "error_count": 0,
                    "failed_clicks": 0,
                    "unnecessary_clicks": 0,
                    "path_deviation_score": 0.0,
                    "page_load_time_ms": 850,
                    "dom_content_loaded_ms": 600,
                    "time_to_first_byte_ms": 120,
                    "feedback_delay_ms": 80,
                    "interaction_to_next_paint_ms": 90,
                    "cumulative_layout_shift": 0.02,
                    "error_message_present": 0,
                    "error_message_clarity": -1,
                    "popup_detected": 0,
                    "cookie_banner_detected": 0,
                    "overlay_blocks_cta": 0,
                },
                {
                    "flow_type": "signup",
                    "viewport_type": "tablet",
                    "task_completed": 1,
                    "task_failed": 0,
                    "completion_time": 9.5,
                    "click_count": 6,
                    "scroll_count": 3,
                    "keyboard_count": 5,
                    "retry_count": 1,
                    "error_count": 1,
                    "failed_clicks": 1,
                    "unnecessary_clicks": 2,
                    "path_deviation_score": 0.35,
                    "page_load_time_ms": 2500,
                    "dom_content_loaded_ms": 1700,
                    "time_to_first_byte_ms": 500,
                    "feedback_delay_ms": 700,
                    "interaction_to_next_paint_ms": 500,
                    "cumulative_layout_shift": 0.12,
                    "error_message_present": 1,
                    "error_message_clarity": 1,
                    "popup_detected": 0,
                    "cookie_banner_detected": 0,
                    "overlay_blocks_cta": 0,
                },
                {
                    "flow_type": "login",
                    "viewport_type": "mobile",
                    "task_completed": 0,
                    "task_failed": 1,
                    "completion_time": 25.0,
                    "click_count": 14,
                    "scroll_count": 6,
                    "keyboard_count": 8,
                    "retry_count": 4,
                    "error_count": 5,
                    "failed_clicks": 6,
                    "unnecessary_clicks": 7,
                    "path_deviation_score": 0.9,
                    "page_load_time_ms": 6500,
                    "dom_content_loaded_ms": 4300,
                    "time_to_first_byte_ms": 1200,
                    "feedback_delay_ms": 2500,
                    "interaction_to_next_paint_ms": 1800,
                    "cumulative_layout_shift": 0.45,
                    "error_message_present": 1,
                    "error_message_clarity": 0,
                    "popup_detected": 1,
                    "cookie_banner_detected": 1,
                    "overlay_blocks_cta": 1,
                },
            ]
        },
    )

    flow_type: str = Field(..., min_length=1)
    viewport_type: str = Field(..., min_length=1)

    task_completed: int = Field(..., ge=0, le=1)
    task_failed: int = Field(..., ge=0, le=1)

    completion_time: float = Field(..., ge=0)
    click_count: int = Field(..., ge=0)
    scroll_count: int = Field(..., ge=0)
    keyboard_count: int = Field(..., ge=0)
    retry_count: int = Field(..., ge=0)
    error_count: int = Field(..., ge=0)
    failed_clicks: int = Field(..., ge=0)
    unnecessary_clicks: int = Field(..., ge=0)

    path_deviation_score: float = Field(..., ge=0)
    page_load_time_ms: float = Field(..., ge=0)
    dom_content_loaded_ms: float = Field(..., ge=0)
    time_to_first_byte_ms: float = Field(..., ge=0)
    feedback_delay_ms: float = Field(..., ge=0)
    interaction_to_next_paint_ms: float = Field(..., ge=0)
    cumulative_layout_shift: float = Field(..., ge=0)

    error_message_present: int = Field(..., ge=0, le=1)
    error_message_clarity: int = Field(..., ge=-1, le=2)
    popup_detected: int = Field(..., ge=0, le=1)
    cookie_banner_detected: int = Field(..., ge=0, le=1)
    overlay_blocks_cta: int = Field(..., ge=0, le=1)

    @field_validator("flow_type")
    @classmethod
    def validate_flow_type(cls, value: str) -> str:
        allowed_values = {
            "landing_navigation",
            "signup",
            "login",
            "search",
            "cta_click",
        }
        if value not in allowed_values:
            raise ValueError(
                f"flow_type must be one of: {sorted(allowed_values)}"
            )
        return value

    @field_validator("viewport_type")
    @classmethod
    def validate_viewport_type(cls, value: str) -> str:
        allowed_values = {"desktop", "tablet", "mobile"}
        if value not in allowed_values:
            raise ValueError(
                f"viewport_type must be one of: {sorted(allowed_values)}"
            )
        return value
    
class AndroidPredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    flow_type: str = Field(..., min_length=1)
    device_type: str = Field(..., min_length=1)
    platform_name: str = Field(..., min_length=1)

    task_completed: int = Field(..., ge=0, le=1)
    task_failed: int = Field(..., ge=0, le=1)

    completion_time: float = Field(..., ge=0)
    click_count: int = Field(..., ge=0)
    scroll_count: int = Field(..., ge=0)
    keyboard_count: int = Field(..., ge=0)
    retry_count: int = Field(..., ge=0)
    error_count: int = Field(..., ge=0)
    failed_clicks: int = Field(..., ge=0)
    unnecessary_clicks: int = Field(..., ge=0)

    path_deviation_score: float = Field(..., ge=0)
    app_launch_time_ms: float = Field(..., ge=0)
    screen_load_time_ms: float = Field(..., ge=0)
    feedback_delay_ms: float = Field(..., ge=0)
    interaction_response_time_ms: float = Field(..., ge=0)
    finish_time_ms: float = Field(..., ge=0)

    error_message_present: int = Field(..., ge=0, le=1)
    error_message_clarity: int = Field(..., ge=-1, le=2)
    popup_detected: int = Field(..., ge=0, le=1)
    overlay_blocks_action: int = Field(..., ge=0, le=1)
    timeout_occurred: int = Field(..., ge=0, le=1)
    crash_detected: int = Field(..., ge=0, le=1)
    anr_detected: int = Field(..., ge=0, le=1)

    @field_validator("flow_type")
    @classmethod
    def validate_flow_type(cls, value: str) -> str:
        value = value.strip().lower()

        flow_mapping = {
            # New generic Appium flows
            "full_app_check": "button_click",
            "basic_navigation": "button_click",
            "button_click": "button_click",
            "form_input": "form_submit",
            "search_flow": "search",

            # Existing model flows
            "login": "login",
            "signup": "signup",
            "search": "search",
            "form_submit": "form_submit",
        }

        if value not in flow_mapping:
            raise ValueError(
                f"Unsupported Android flow_type: {value}. "
                f"Allowed values: {sorted(flow_mapping.keys())}"
            )

        # Return a value understood by the trained Android model.
        return flow_mapping[value]


class AndroidPredictionResponse(BaseModel):
    model_type: str
    prediction: str
    confidence_score: Optional[float] = None
    class_probabilities: Optional[Dict[str, float]] = None
    recommendations: List[str]


class BaselinePredictionInput(BaseModel):
    model_config = ConfigDict(
        extra="forbid",
        json_schema_extra={
            "examples": [
                {
                    "completion_time": 2.5,
                    "click_count": 2,
                    "scroll_count": 1,
                    "keyboard_count": 0,
                    "retry_count": 0,
                    "error_count": 0,
                    "failed_clicks": 0,
                    "task_completed": 1,
                },
                {
                    "completion_time": 25.0,
                    "click_count": 14,
                    "scroll_count": 6,
                    "keyboard_count": 8,
                    "retry_count": 4,
                    "error_count": 5,
                    "failed_clicks": 6,
                    "task_completed": 0,
                },
            ]
        },
    )

    completion_time: float = Field(..., ge=0)
    click_count: int = Field(..., ge=0)
    scroll_count: int = Field(..., ge=0)
    keyboard_count: int = Field(..., ge=0)
    retry_count: int = Field(..., ge=0)
    error_count: int = Field(..., ge=0)
    failed_clicks: int = Field(..., ge=0)
    task_completed: int = Field(..., ge=0, le=1)


class PredictionResponse(BaseModel):
    status: str
    model_name: str
    friction_level: str
    confidence_score: Optional[float] = None
    class_probabilities: Optional[Dict[str, float]] = None
    recommendation: List[str]
    input_features: Dict[str, Any]


class BatchGAgentPredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    items: List[GAgentPredictionInput] = Field(..., min_length=1)


class BatchBaselinePredictionInput(BaseModel):
    model_config = ConfigDict(extra="forbid")

    items: List[BaselinePredictionInput] = Field(..., min_length=1)


class BatchPredictionResponse(BaseModel):
    status: str
    model_name: str
    total_predictions: int
    predictions: List[PredictionResponse]