import json
import urllib.error
import urllib.request


API_URL = "http://127.0.0.1:8001/predict"


SAMPLES = [
    {
        "sample_name": "Low friction sample",
        "payload": {
            "completion_time": 1200,
            "click_count": 3,
            "scroll_count": 0,
            "keyboard_count": 2,
            "retry_count": 0,
            "error_count": 0,
            "failed_clicks": 0,
            "feedback_delay": 300,
            "task_completed": 1,
            "screenshot_count": 2,
            "error_message_clarity": 2,
        },
    },
    {
        "sample_name": "Medium friction sample",
        "payload": {
            "completion_time": 6500,
            "click_count": 8,
            "scroll_count": 3,
            "keyboard_count": 5,
            "retry_count": 2,
            "error_count": 1,
            "failed_clicks": 1,
            "feedback_delay": 1200,
            "task_completed": 1,
            "screenshot_count": 2,
            "error_message_clarity": 1,
        },
    },
    {
        "sample_name": "High friction sample",
        "payload": {
            "completion_time": 18000,
            "click_count": 18,
            "scroll_count": 8,
            "keyboard_count": 10,
            "retry_count": 5,
            "error_count": 4,
            "failed_clicks": 5,
            "feedback_delay": 3500,
            "task_completed": 0,
            "screenshot_count": 5,
            "error_message_clarity": 0,
        },
    },
]


def post_json(url: str, payload: dict) -> dict:
    data = json.dumps(payload).encode("utf-8")

    request = urllib.request.Request(
        url=url,
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )

    with urllib.request.urlopen(request, timeout=10) as response:
        response_body = response.read().decode("utf-8")
        return json.loads(response_body)


def main() -> None:
    print("=" * 80)
    print("GAgent FastAPI Prediction Test")
    print("=" * 80)

    for sample in SAMPLES:
        print(f"\nTesting: {sample['sample_name']}")

        try:
            result = post_json(API_URL, sample["payload"])
            print(json.dumps(result, indent=4))
        except urllib.error.HTTPError as error:
            print(f"HTTP error: {error.code}")
            print(error.read().decode("utf-8"))
        except urllib.error.URLError as error:
            print(f"Connection error: {error}")
            print("Make sure FastAPI is running at http://127.0.0.1:8001")
        except Exception as error:
            print(f"Unexpected error: {error}")

    print("\nTest completed.")


if __name__ == "__main__":
    main()