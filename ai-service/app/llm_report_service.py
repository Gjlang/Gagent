import json
import os
from typing import Any, Dict, List, Optional
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


DEFAULT_OLLAMA_URL = "http://127.0.0.1:11434"
DEFAULT_OLLAMA_MODEL = "llama3.2:1b"


def _number(
    metrics: Dict[str, Any],
    key: str,
    default: float = 0.0,
) -> float:
    """
    Safely convert one metric value to a number.
    """

    try:
        return float(metrics.get(key, default) or default)
    except (TypeError, ValueError):
        return default


def _flag(metrics: Dict[str, Any], key: str) -> bool:
    """
    Convert a numeric 0/1 metric into a Boolean value.
    """

    return int(_number(metrics, key, 0)) == 1


def _fallback_recommendations(
    metrics: Dict[str, Any],
    level: str,
) -> List[str]:
    """
    Produce recommendations without using an LLM.

    This is used when Ollama is stopped, unavailable,
    too slow, or returns invalid JSON.
    """

    recommendations: List[str] = []

    page_load_time = _number(metrics, "page_load_time_ms")
    screen_load_time = _number(metrics, "screen_load_time_ms")

    if page_load_time >= 3000 or screen_load_time >= 3000:
        recommendations.append(
            "Reduce page or screen loading time and remove unnecessary blocking work."
        )

    feedback_delay = _number(metrics, "feedback_delay_ms")
    response_time = _number(
        metrics,
        "interaction_response_time_ms",
    )

    if feedback_delay >= 1000 or response_time >= 1000:
        recommendations.append(
            "Provide faster visual feedback after clicks, taps, and form actions."
        )

    if _number(metrics, "failed_clicks") >= 2:
        recommendations.append(
            "Improve control visibility, clickability, tap target size, and disabled-state feedback."
        )

    if _number(metrics, "retry_count") >= 2:
        recommendations.append(
            "Simplify the task flow so users do not need repeated attempts."
        )

    if _number(metrics, "error_count") >= 1:
        recommendations.append(
            "Fix task-blocking errors and provide clear recovery instructions."
        )

    if (
        _flag(metrics, "overlay_blocks_cta")
        or _flag(metrics, "overlay_blocks_action")
    ):
        recommendations.append(
            "Prevent overlays, modals, or banners from blocking the main action."
        )

    if _flag(metrics, "popup_detected"):
        recommendations.append(
            "Avoid interruptive popups during the main user journey."
        )

    if _flag(metrics, "cookie_banner_detected"):
        recommendations.append(
            "Keep the cookie banner clear without covering the primary action."
        )

    if _flag(metrics, "timeout_occurred"):
        recommendations.append(
            "Prevent timeouts and show progress or recovery guidance for long operations."
        )

    if (
        _flag(metrics, "crash_detected")
        or _flag(metrics, "anr_detected")
    ):
        recommendations.append(
            "Investigate crashes or unresponsive states before further UX optimisation."
        )

    if _number(metrics, "cumulative_layout_shift") >= 0.25:
        recommendations.append(
            "Stabilise the layout so controls do not move unexpectedly."
        )

    error_message_present = _flag(
        metrics,
        "error_message_present",
    )

    error_message_clarity = int(
        _number(metrics, "error_message_clarity", -1)
    )

    if error_message_present and error_message_clarity <= 1:
        recommendations.append(
            "Rewrite error messages to explain the problem and the next recovery step."
        )

    if not recommendations:
        if level == "Low":
            recommendations.append(
                "Maintain the current flow and continue monitoring performance and accessibility."
            )
        elif level == "Medium":
            recommendations.append(
                "Review the slowest and most repeated steps before they become task blockers."
            )
        else:
            recommendations.append(
                "Review the complete journey because the ML model detected high UX friction."
            )

    return recommendations[:5]


def _fallback_reason(
    metrics: Dict[str, Any],
    level: str,
) -> str:
    """
    Produce a readable explanation from the supplied metrics.
    """

    reasons: List[str] = []

    task_completed = int(
        _number(metrics, "task_completed", 1)
    )

    if (
        task_completed == 0
        or _flag(metrics, "task_failed")
    ):
        reasons.append("the task was not completed")

    if _number(metrics, "feedback_delay_ms") >= 1000:
        reasons.append("action feedback was delayed")

    if (
        _number(metrics, "page_load_time_ms") >= 3000
        or _number(metrics, "screen_load_time_ms") >= 3000
    ):
        reasons.append("loading time was high")

    if _number(metrics, "failed_clicks") >= 2:
        reasons.append(
            "multiple failed clicks or taps were recorded"
        )

    if _number(metrics, "retry_count") >= 2:
        reasons.append("the user needed repeated attempts")

    if _number(metrics, "error_count") >= 1:
        reasons.append("errors occurred during the flow")

    if (
        _flag(metrics, "overlay_blocks_cta")
        or _flag(metrics, "overlay_blocks_action")
    ):
        reasons.append(
            "an overlay blocked the primary action"
        )

    if _flag(metrics, "timeout_occurred"):
        reasons.append("a timeout occurred")

    if _flag(metrics, "crash_detected"):
        reasons.append("the application crashed")

    if _flag(metrics, "anr_detected"):
        reasons.append(
            "the application became unresponsive"
        )

    if not reasons:
        return (
            f"The ML model classified this journey as "
            f"{level} friction based on the submitted UX metrics."
        )

    return (
        f"The ML model classified this journey as "
        f"{level} friction because "
        + ", ".join(reasons[:4])
        + "."
    )


def build_fallback(
    payload: Dict[str, Any],
    model_name: str,
    error: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Return a valid explanation even when Ollama is offline.
    """

    level = str(
        payload.get("friction_level", "Unknown")
    )

    metrics = payload.get("metrics") or {}

    risk_reason = _fallback_reason(
        metrics,
        level,
    )

    summary = (
        "LLM service is unavailable. "
        "This explanation was generated using the "
        "rule-based fallback. "
        + risk_reason
    )

    if error:
        summary += (
            " Ollama connection details were recorded "
            "in the FastAPI log."
        )

    return {
        "summary": summary,
        "explanation": risk_reason,
        "recommendations": _fallback_recommendations(
            metrics,
            level,
        ),
        "risk_reason": risk_reason,
        "llm_used": False,
        "model_name": model_name,
    }


def check_ollama_available(
    base_url: Optional[str] = None,
    timeout: float = 1.5,
) -> bool:
    """
    Check whether the local Ollama API is reachable.
    """

    selected_base_url = (
        base_url
        or os.getenv(
            "OLLAMA_BASE_URL",
            DEFAULT_OLLAMA_URL,
        )
    ).rstrip("/")

    url = selected_base_url + "/api/tags"

    try:
        request = Request(
            url,
            method="GET",
        )

        with urlopen(
            request,
            timeout=timeout,
        ) as response:
            return 200 <= response.status < 300

    except (
        HTTPError,
        URLError,
        TimeoutError,
        OSError,
    ):
        return False


def _build_prompt(payload: Dict[str, Any]) -> str:
    """
    Build the strict Ollama prompt.

    The model is explicitly forbidden from changing
    the ML prediction.
    """

    input_json = json.dumps(
        payload,
        ensure_ascii=False,
        indent=2,
    )

    return f"""
You are an AI UX report assistant for GAgent.

STRICT RULES:

- The machine-learning model already predicted the friction level.
- Do not change, challenge, or recalculate that prediction.
- The friction level is final.
- Use only the supplied platform, flow, confidence, probabilities, metrics, and existing recommendations.
- Do not invent metrics.
- Do not invent causes.
- Do not invent user behaviour.
- Do not invent screenshots.
- Do not invent application failures.
- If evidence is incomplete, state that the explanation is limited to the available metrics.
- Use concise and professional UX audit language.
- Return valid JSON only.
- Do not return Markdown.
- Do not use code fences.

Return exactly this JSON structure:

{{
  "summary": "one short paragraph",
  "explanation": "why the fixed ML prediction is consistent with the supplied evidence",
  "recommendations": [
    "recommendation 1",
    "recommendation 2",
    "recommendation 3"
  ],
  "risk_reason": "one concise main reason"
}}

INPUT DATA:

{input_json}
""".strip()


def _normalise_response(
    data: Dict[str, Any],
    model_name: str,
) -> Dict[str, Any]:
    """
    Convert the Ollama response into the API response format.
    """

    recommendations = data.get("recommendations")

    if not isinstance(recommendations, list):
        recommendations = []

    recommendations = [
        str(item).strip()
        for item in recommendations
        if str(item).strip()
    ][:5]

    return {
        "summary": str(
            data.get("summary", "")
        ).strip(),
        "explanation": str(
            data.get("explanation", "")
        ).strip(),
        "recommendations": recommendations,
        "risk_reason": str(
            data.get("risk_reason", "")
        ).strip(),
        "llm_used": True,
        "model_name": model_name,
    }


def generate_report_explanation(
    payload: Dict[str, Any],
    ollama_base_url: Optional[str] = None,
    model_name: Optional[str] = None,
    timeout: float = 60.0,
) -> Dict[str, Any]:
    """
    Generate the report explanation through Ollama.

    This function never predicts Low, Medium, or High.
    It only explains the friction level supplied in payload.
    """

    base_url = (
        ollama_base_url
        or os.getenv(
            "OLLAMA_BASE_URL",
            DEFAULT_OLLAMA_URL,
        )
    ).rstrip("/")

    selected_model = (
        model_name
        or os.getenv(
            "OLLAMA_MODEL",
            DEFAULT_OLLAMA_MODEL,
        )
    )

    request_body = {
        "model": selected_model,
        "prompt": _build_prompt(payload),
        "stream": False,
        "format": "json",
        "options": {
            "temperature": 0.2,
        },
    }

    try:
        request = Request(
            base_url + "/api/generate",
            data=json.dumps(
                request_body
            ).encode("utf-8"),
            headers={
                "Content-Type": "application/json",
            },
            method="POST",
        )

        with urlopen(
            request,
            timeout=timeout,
        ) as response:
            raw_response = response.read().decode(
                "utf-8"
            )

        raw_data = json.loads(raw_response)

        generated_text = raw_data.get(
            "response",
            "",
        )

        if isinstance(generated_text, str):
            parsed_data = json.loads(
                generated_text
            )
        else:
            parsed_data = generated_text

        if not isinstance(parsed_data, dict):
            raise ValueError(
                "Ollama response was not a JSON object."
            )

        result = _normalise_response(
            parsed_data,
            selected_model,
        )

        if (
            not result["summary"]
            or not result["explanation"]
            or not result["risk_reason"]
        ):
            raise ValueError(
                "Ollama response missed required report fields."
            )

        if not result["recommendations"]:
            result["recommendations"] = (
                _fallback_recommendations(
                    payload.get("metrics") or {},
                    str(
                        payload.get(
                            "friction_level",
                            "Unknown",
                        )
                    ),
                )
            )

        return result

    except (
        HTTPError,
        URLError,
        TimeoutError,
        OSError,
        json.JSONDecodeError,
        ValueError,
    ) as error:
        return build_fallback(
            payload,
            selected_model,
            str(error),
        )