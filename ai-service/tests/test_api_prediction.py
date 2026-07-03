import json
import sys
import urllib.error
import urllib.request


BASE_URL = "http://127.0.0.1:8001"


LOW_GAGENT_SAMPLE = {
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
}


MEDIUM_GAGENT_SAMPLE = {
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
}


HIGH_GAGENT_SAMPLE = {
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
}


BASELINE_SAMPLE = {
    "completion_time": 25.0,
    "click_count": 14,
    "scroll_count": 6,
    "keyboard_count": 8,
    "retry_count": 4,
    "error_count": 5,
    "failed_clicks": 6,
    "task_completed": 0,
}


def request_json(method, path, payload=None):
    url = f"{BASE_URL}{path}"

    data = None
    headers = {}

    if payload is not None:
        data = json.dumps(payload).encode("utf-8")
        headers["Content-Type"] = "application/json"

    request = urllib.request.Request(
        url=url,
        data=data,
        headers=headers,
        method=method,
    )

    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            response_body = response.read().decode("utf-8")
            return response.status, json.loads(response_body)
    except urllib.error.HTTPError as error:
        error_body = error.read().decode("utf-8")
        print(f"HTTP error for {method} {path}: {error.code}")
        print(error_body)
        raise
    except Exception as error:
        print(f"Request failed for {method} {path}: {error}")
        raise


def assert_success_prediction(response):
    assert response["status"] == "success"
    assert response["friction_level"] in {"Low", "Medium", "High"}
    assert "confidence_score" in response
    assert "class_probabilities" in response
    assert "recommendation" in response
    assert isinstance(response["recommendation"], list)


def main():
    print("Testing /health")
    status, response = request_json("GET", "/health")
    assert status == 200
    assert response["model_loaded"] is True
    print("PASS /health")

    print("Testing /model-info")
    status, response = request_json("GET", "/model-info")
    assert status == 200
    assert response["status"] == "success"
    assert response["model_loaded"] is True
    assert "main_gagent_model" in response["models"]
    assert "baseline_model" in response["models"]
    print("PASS /model-info")

    print("Testing /predict-gagent LOW sample")
    status, response = request_json("POST", "/predict-gagent", LOW_GAGENT_SAMPLE)
    assert status == 200
    assert_success_prediction(response)
    print("PASS /predict-gagent LOW:", response["friction_level"])

    print("Testing /predict-gagent MEDIUM sample")
    status, response = request_json("POST", "/predict-gagent", MEDIUM_GAGENT_SAMPLE)
    assert status == 200
    assert_success_prediction(response)
    print("PASS /predict-gagent MEDIUM:", response["friction_level"])

    print("Testing /predict-gagent HIGH sample")
    status, response = request_json("POST", "/predict-gagent", HIGH_GAGENT_SAMPLE)
    assert status == 200
    assert_success_prediction(response)
    print("PASS /predict-gagent HIGH:", response["friction_level"])

    print("Testing /predict-baseline")
    status, response = request_json("POST", "/predict-baseline", BASELINE_SAMPLE)
    assert status == 200
    assert_success_prediction(response)
    print("PASS /predict-baseline:", response["friction_level"])

    print("Testing /batch-predict-gagent")
    status, response = request_json(
        "POST",
        "/batch-predict-gagent",
        {
            "items": [
                LOW_GAGENT_SAMPLE,
                MEDIUM_GAGENT_SAMPLE,
                HIGH_GAGENT_SAMPLE,
            ]
        },
    )
    assert status == 200
    assert response["status"] == "success"
    assert response["model_name"] == "main_gagent_model"
    assert response["total_predictions"] == 3
    assert len(response["predictions"]) == 3
    print("PASS /batch-predict-gagent")

    print("All Phase 5 API tests passed.")


if __name__ == "__main__":
    try:
        main()
    except AssertionError:
        print("Test failed because an expected response value was not returned.")
        sys.exit(1)
    except Exception:
        print("Test failed because the API request failed.")
        sys.exit(1)