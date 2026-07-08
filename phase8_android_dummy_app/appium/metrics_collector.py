import time
from dataclasses import dataclass, asdict


@dataclass
class AndroidUXMetrics:
    target_type: str = "android_app"
    flow_type: str = ""
    scenario_type: str = ""
    device_type: str = "android_emulator"
    platform_name: str = "Android"

    task_completed: int = 0
    task_failed: int = 0

    completion_time: float = 0.0
    click_count: int = 0
    scroll_count: int = 0
    keyboard_count: int = 0
    retry_count: int = 0
    error_count: int = 0
    failed_clicks: int = 0
    unnecessary_clicks: int = 0
    path_deviation_score: int = 0

    app_launch_time_ms: int = 0
    screen_load_time_ms: int = 0
    feedback_delay_ms: int = 0
    interaction_response_time_ms: int = 0
    finish_time_ms: int = 0

    error_message_present: int = 0
    error_message_clarity: str = "none"

    popup_detected: int = 0
    overlay_blocks_action: int = 0
    timeout_occurred: int = 0
    crash_detected: int = 0
    anr_detected: int = 0

    friction_level_prediction: str = ""

    def to_dict(self):
        return asdict(self)


class MetricsTimer:
    def __init__(self):
        self.start = time.perf_counter()

    def elapsed_seconds(self) -> float:
        return round(time.perf_counter() - self.start, 4)

    def elapsed_ms(self) -> int:
        return int((time.perf_counter() - self.start) * 1000)


def classify_error_clarity(text: str) -> str:
    if not text:
        return "none"

    text_lower = text.lower()

    vague_terms = [
        "error",
        "invalid form",
        "something went wrong",
        "no result"
    ]

    clear_terms = [
        "email",
        "required",
        "must contain",
        "timed out",
        "try again",
        "full name"
    ]

    if any(term in text_lower for term in clear_terms):
        return "clear"

    if any(term in text_lower for term in vague_terms):
        return "vague"

    return "medium"