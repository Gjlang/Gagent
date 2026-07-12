import json
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


PROJECT_ROOT = Path(
    __file__
).resolve().parents[1]

if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(
        0,
        str(PROJECT_ROOT),
    )


from app.llm_report_service import (
    generate_report_explanation,
)


BASE_URL = "http://127.0.0.1:8001"


def request_json(
    method: str,
    endpoint: str,
    payload=None,
):
    body = (
        None
        if payload is None
        else json.dumps(
            payload
        ).encode("utf-8")
    )

    request = Request(
        BASE_URL + endpoint,
        data=body,
        headers={
            "Content-Type": "application/json",
        },
        method=method,
    )

    try:
        with urlopen(
            request,
            timeout=90,
        ) as response:
            response_body = (
                response.read().decode("utf-8")
            )

            return (
                response.status,
                json.loads(response_body),
            )

    except HTTPError as error:
        details = error.read().decode(
            "utf-8",
            errors="replace",
        )

        raise AssertionError(
            f"{endpoint} returned HTTP "
            f"{error.code}: {details}"
        ) from error

    except URLError as error:
        raise AssertionError(
            "FastAPI is not running at "
            "http://127.0.0.1:8001"
        ) from error


def sample(level: str) -> dict:
    samples = {
        "High": {
            "task_completed": 0,
            "task_failed": 1,
            "completion_time": 12.5,
            "click_count": 8,
            "failed_clicks": 3,
            "retry_count": 2,
            "error_count": 1,
            "feedback_delay_ms": 2500,
            "popup_detected": 1,
            "overlay_blocks_cta": 1,
        },
        "Medium": {
            "task_completed": 1,
            "task_failed": 0,
            "completion_time": 8.2,
            "click_count": 6,
            "failed_clicks": 1,
            "retry_count": 1,
            "error_count": 1,
            "feedback_delay_ms": 800,
            "popup_detected": 0,
            "overlay_blocks_cta": 0,
        },
        "Low": {
            "task_completed": 1,
            "task_failed": 0,
            "completion_time": 2.1,
            "click_count": 2,
            "failed_clicks": 0,
            "retry_count": 0,
            "error_count": 0,
            "feedback_delay_ms": 90,
            "popup_detected": 0,
            "overlay_blocks_cta": 0,
        },
    }

    probabilities = {
        "High": {
            "Low": 0.02,
            "Medium": 0.07,
            "High": 0.91,
        },
        "Medium": {
            "Low": 0.12,
            "Medium": 0.76,
            "High": 0.12,
        },
        "Low": {
            "Low": 0.94,
            "Medium": 0.05,
            "High": 0.01,
        },
    }

    return {
        "platform": "web",
        "flow_type": "cta_click",
        "friction_level": level,
        "confidence_score": max(
            probabilities[level].values()
        ),
        "class_probabilities": (
            probabilities[level]
        ),
        "metrics": samples[level],
        "existing_recommendations": [],
    }


def assert_response(data: dict) -> None:
    required = {
        "summary",
        "explanation",
        "recommendations",
        "risk_reason",
        "llm_used",
        "model_name",
    }

    missing = required - set(data.keys())

    assert not missing, (
        f"Missing response fields: {missing}"
    )

    assert isinstance(
        data["recommendations"],
        list,
    )

    assert data["recommendations"]


def main() -> None:
    status, health = request_json(
        "GET",
        "/health",
    )

    assert status == 200
    assert "status" in health

    print("PASS /health")

    for level in (
        "High",
        "Medium",
        "Low",
    ):
        status, data = request_json(
            "POST",
            "/generate-report-explanation",
            sample(level),
        )

        assert status == 200

        assert_response(data)

        combined_text = (
            data["summary"]
            + " "
            + data["explanation"]
            + " "
            + data["risk_reason"]
        )

        assert level in combined_text

        print(
            "PASS /generate-report-explanation "
            + level.upper()
        )

    fallback = generate_report_explanation(
        sample("High"),
        ollama_base_url=(
            "http://127.0.0.1:1"
        ),
        timeout=0.2,
    )

    assert_response(fallback)

    assert fallback["llm_used"] is False

    print("PASS Ollama offline fallback")

    print(
        "All LLM report explanation tests passed"
    )


if __name__ == "__main__":
    main()