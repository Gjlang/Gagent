import json
import sys
import urllib.error
import urllib.request


BASE_URL = "http://127.0.0.1:8001"


LOW_SAMPLE = {
    "flow_type": "login",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 1,
    "task_failed": 0,
    "completion_time": 5.0,
    "click_count": 3,
    "scroll_count": 0,
    "keyboard_count": 2,
    "retry_count": 0,
    "error_count": 0,
    "failed_clicks": 0,
    "unnecessary_clicks": 0,
    "path_deviation_score": 0.05,
    "app_launch_time_ms": 800,
    "screen_load_time_ms": 600,
    "feedback_delay_ms": 250,
    "interaction_response_time_ms": 300,
    "finish_time_ms": 5200,
    "error_message_present": 0,
    "error_message_clarity": -1,
    "popup_detected": 0,
    "overlay_blocks_action": 0,
    "timeout_occurred": 0,
    "crash_detected": 0,
    "anr_detected": 0,
}

MEDIUM_SAMPLE = {
    "flow_type": "signup",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 1,
    "task_failed": 0,
    "completion_time": 12.0,
    "click_count": 7,
    "scroll_count": 2,
    "keyboard_count": 4,
    "retry_count": 1,
    "error_count": 1,
    "failed_clicks": 1,
    "unnecessary_clicks": 2,
    "path_deviation_score": 0.35,
    "app_launch_time_ms": 1300,
    "screen_load_time_ms": 1700,
    "feedback_delay_ms": 1200,
    "interaction_response_time_ms": 1500,
    "finish_time_ms": 12500,
    "error_message_present": 1,
    "error_message_clarity": 1,
    "popup_detected": 1,
    "overlay_blocks_action": 0,
    "timeout_occurred": 0,
    "crash_detected": 0,
    "anr_detected": 0,
}

HIGH_SAMPLE = {
    "flow_type": "form_submit",
    "device_type": "android_emulator",
    "platform_name": "Android",
    "task_completed": 0,
    "task_failed": 1,
    "completion_time": 27.0,
    "click_count": 15,
    "scroll_count": 4,
    "keyboard_count": 5,
    "retry_count": 4,
    "error_count": 5,
    "failed_clicks": 5,
    "unnecessary_clicks": 6,
    "path_deviation_score": 0.85,
    "app_launch_time_ms": 2500,
    "screen_load_time_ms": 5200,
    "feedback_delay_ms": 4300,
    "interaction_response_time_ms": 5000,
    "finish_time_ms": 30000,
    "error_message_present": 1,
    "error_message_clarity": 0,
    "popup_detected": 1,
    "overlay_blocks_action": 1,
    "timeout_occurred": 1,
    "crash_detected": 0,
    "anr_detected": 1,
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
            body = response.read().decode("utf-8")
            return response.status, json.loads(body)
    except urllib.error.HTTPError as error:
        print(f"HTTP error {error.code} for {method} {path}")
        print(error.read().decode("utf-8"))
        raise
    except Exception as error:
        print(f"Request failed for {method} {path}: {error}")
        raise


def assert_android_response(response):
    assert response["model_type"] == "android_appium"
    assert response["prediction"] in {"Low", "Medium", "High"}
    assert "confidence_score" in response
    assert "class_probabilities" in response
    assert isinstance(response["recommendations"], list)


def main():
    print("Testing /health")
    status, response = request_json("GET", "/health")
    assert status == 200
    print("PASS /health")

    print("Testing /model-info")
    status, response = request_json("GET", "/model-info")
    assert status == 200
    print("PASS /model-info")

    print("Testing /predict-android LOW")
    status, response = request_json("POST", "/predict-android", LOW_SAMPLE)
    assert status == 200
    assert_android_response(response)
    print("PASS /predict-android LOW:", response["prediction"])

    print("Testing /predict-android MEDIUM")
    status, response = request_json("POST", "/predict-android", MEDIUM_SAMPLE)
    assert status == 200
    assert_android_response(response)
    print("PASS /predict-android MEDIUM:", response["prediction"])

    print("Testing /predict-android HIGH")
    status, response = request_json("POST", "/predict-android", HIGH_SAMPLE)
    assert status == 200
    assert_android_response(response)
    print("PASS /predict-android HIGH:", response["prediction"])

    print("All Android API tests passed")


if __name__ == "__main__":
    try:
        main()
    except Exception:
        print("Android API test failed.")
        sys.exit(1)