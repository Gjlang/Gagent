from __future__ import annotations

import argparse
import json
import sys
import time
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Any, Iterable

from appium import webdriver
from appium.options.android import UiAutomator2Options
from appium.webdriver.common.appiumby import AppiumBy
from selenium.common.exceptions import WebDriverException


DUMMY_PACKAGE = "com.gagent.dummyandroid"
DUMMY_ACTIVITY = "com.gagent.dummyandroid.MainActivity"

SAFE_TEXT = "test"

BLOCKED_WORDS = {
    "buy",
    "purchase",
    "payment",
    "pay now",
    "checkout",
    "transfer",
    "bank",
    "wallet",
    "delete account",
    "remove account",
    "unsubscribe",
    "logout",
    "sign out",
    "login",
    "log in",
    "sign in",
    "register",
    "sign up",
    "password",
    "otp",
    "verification",
    "verify identity",
    "biometric",
    "fingerprint",
    "camera",
    "contact",
    "call",
    "emergency",
    "sos",
    "send money",
    "submit order",
    "confirm order",
}


@dataclass
class AndroidMetrics:
    flow_type: str
    device_type: str = "phone"
    platform_name: str = "Android"

    task_completed: int = 0
    task_failed: int = 1

    completion_time: float = 0.0
    click_count: int = 0
    scroll_count: int = 0
    keyboard_count: int = 0
    retry_count: int = 0
    error_count: int = 0
    failed_clicks: int = 0
    unnecessary_clicks: int = 0

    path_deviation_score: float = 0.0

    app_launch_time_ms: float = 0.0
    screen_load_time_ms: float = 0.0
    feedback_delay_ms: float = 0.0
    interaction_response_time_ms: float = 0.0
    finish_time_ms: float = 0.0

    error_message_present: int = 0
    error_message_clarity: int = -1

    popup_detected: int = 0
    overlay_blocks_action: int = 0
    timeout_occurred: int = 0
    crash_detected: int = 0
    anr_detected: int = 0


class SafeRunner:
    def __init__(
        self,
        driver: webdriver.Remote,
        flow: str,
        max_duration: int,
    ) -> None:
        self.driver = driver
        self.flow = flow
        self.max_duration = max_duration
        self.metrics = AndroidMetrics(flow_type=flow)
        self.started_at = time.perf_counter()

    def elapsed_seconds(self) -> float:
        return time.perf_counter() - self.started_at

    def ensure_time_available(self) -> None:
        if self.elapsed_seconds() > self.max_duration:
            self.metrics.timeout_occurred = 1
            raise TimeoutError(
                f"Maximum duration of {self.max_duration} seconds was reached."
            )

    @staticmethod
    def element_text(element: Any) -> str:
        candidates = [
            getattr(element, "text", ""),
            element.get_attribute("text"),
            element.get_attribute("content-desc"),
            element.get_attribute("resource-id"),
            element.get_attribute("hint"),
        ]

        return " ".join(
            str(value).strip()
            for value in candidates
            if value
        ).lower()

    @staticmethod
    def is_displayed_and_enabled(element: Any) -> bool:
        try:
            return bool(
                element.is_displayed()
                and element.is_enabled()
            )
        except Exception:
            return False

    def is_safe_element(self, element: Any) -> bool:
        text = self.element_text(element)

        if not text:
            return True

        return not any(
            blocked_word in text
            for blocked_word in BLOCKED_WORDS
        )

    def page_signature(self) -> str:
        try:
            source = self.driver.page_source or ""
            return source[:20000]
        except Exception:
            return ""

    def detect_system_popup(self) -> None:
        permission_ids = [
            "com.android.permissioncontroller:id/permission_allow_button",
            "com.android.permissioncontroller:id/permission_allow_foreground_only_button",
            "com.android.permissioncontroller:id/permission_allow_one_time_button",
            "com.android.permissioncontroller:id/permission_deny_button",
            "android:id/button1",
            "android:id/button2",
        ]

        for resource_id in permission_ids:
            try:
                elements = self.driver.find_elements(
                    AppiumBy.ID,
                    resource_id,
                )

                if any(
                    self.is_displayed_and_enabled(element)
                    for element in elements
                ):
                    self.metrics.popup_detected = 1
                    self.metrics.overlay_blocks_action = 1
                    return
            except Exception:
                continue

    def detect_error_text(self) -> None:
        error_words = [
            "error",
            "invalid",
            "failed",
            "required",
            "not found",
            "try again",
            "something went wrong",
            "unavailable",
        ]

        try:
            texts = self.driver.find_elements(
                AppiumBy.XPATH,
                "//*[@text or @content-desc]",
            )

            for element in texts:
                text = self.element_text(element)

                if any(word in text for word in error_words):
                    self.metrics.error_message_present = 1

                    if len(text.strip()) >= 20:
                        self.metrics.error_message_clarity = 2
                    elif len(text.strip()) >= 8:
                        self.metrics.error_message_clarity = 1
                    else:
                        self.metrics.error_message_clarity = 0

                    return

        except Exception:
            return

    def find_clickable_elements(self) -> list[Any]:
        candidates: list[Any] = []

        locators = [
            (
                AppiumBy.XPATH,
                "//*[@clickable='true' and @enabled='true']",
            ),
            (
                AppiumBy.CLASS_NAME,
                "android.widget.Button",
            ),
            (
                AppiumBy.CLASS_NAME,
                "android.widget.ImageButton",
            ),
        ]

        seen_ids: set[str] = set()

        for by, value in locators:
            try:
                elements = self.driver.find_elements(by, value)
            except Exception:
                continue

            for element in elements:
                try:
                    element_id = element.id
                except Exception:
                    element_id = str(id(element))

                if element_id in seen_ids:
                    continue

                seen_ids.add(element_id)

                if not self.is_displayed_and_enabled(element):
                    continue

                if not self.is_safe_element(element):
                    continue

                candidates.append(element)

        return candidates

    def find_editable_fields(self) -> list[Any]:
        fields: list[Any] = []

        locators = [
            (
                AppiumBy.CLASS_NAME,
                "android.widget.EditText",
            ),
            (
                AppiumBy.XPATH,
                "//*[@editable='true' and @enabled='true']",
            ),
        ]

        seen_ids: set[str] = set()

        for by, value in locators:
            try:
                elements = self.driver.find_elements(by, value)
            except Exception:
                continue

            for element in elements:
                try:
                    element_id = element.id
                except Exception:
                    element_id = str(id(element))

                if element_id in seen_ids:
                    continue

                seen_ids.add(element_id)

                if not self.is_displayed_and_enabled(element):
                    continue

                text = self.element_text(element)

                if any(
                    blocked in text
                    for blocked in [
                        "password",
                        "pin",
                        "otp",
                        "card",
                        "account",
                        "phone",
                    ]
                ):
                    continue

                fields.append(element)

        return fields

    def find_search_field(self) -> Any | None:
        fields = self.find_editable_fields()

        for field in fields:
            text = self.element_text(field)

            if "search" in text:
                return field

        return fields[0] if fields else None

    def safe_click(self, element: Any) -> bool:
        self.ensure_time_available()

        if not self.is_safe_element(element):
            self.metrics.unnecessary_clicks += 1
            return False

        started = time.perf_counter()

        try:
            element.click()
            self.metrics.click_count += 1
            self.metrics.interaction_response_time_ms = round(
                (time.perf_counter() - started) * 1000,
                2,
            )
            return True

        except Exception:
            self.metrics.failed_clicks += 1
            self.metrics.error_count += 1
            return False

    def safe_type(self, element: Any, value: str) -> bool:
        self.ensure_time_available()

        try:
            element.click()
            element.clear()
            element.send_keys(value)
            self.metrics.keyboard_count += len(value)
            return True

        except Exception:
            self.metrics.error_count += 1
            self.metrics.retry_count += 1
            return False

    def basic_navigation(self) -> None:
        self.detect_system_popup()

        before = self.page_signature()
        elements = self.find_clickable_elements()

        if not elements:
            self.metrics.error_count += 1
            self.metrics.task_failed = 1
            return

        for element in elements[:5]:
            self.ensure_time_available()

            if self.safe_click(element):
                time.sleep(1)
                after = self.page_signature()

                if after and before and after != before:
                    self.metrics.task_completed = 1
                    self.metrics.task_failed = 0
                    self.metrics.feedback_delay_ms = (
                        self.metrics.interaction_response_time_ms
                    )
                    return

                self.metrics.unnecessary_clicks += 1

        self.metrics.task_failed = 1

    def button_click(self) -> None:
        self.detect_system_popup()

        before = self.page_signature()
        elements = self.find_clickable_elements()

        if not elements:
            self.metrics.error_count += 1
            return

        for element in elements[:8]:
            started = time.perf_counter()

            if not self.safe_click(element):
                continue

            time.sleep(0.8)

            delay_ms = (
                time.perf_counter() - started
            ) * 1000

            self.metrics.feedback_delay_ms = round(
                delay_ms,
                2,
            )

            after = self.page_signature()

            if after and before and after != before:
                self.metrics.task_completed = 1
                self.metrics.task_failed = 0
                return

            self.metrics.unnecessary_clicks += 1

        self.metrics.task_failed = 1

    def form_input(self) -> None:
        self.detect_system_popup()

        fields = self.find_editable_fields()

        if not fields:
            self.metrics.error_count += 1
            return

        typed = False

        for field in fields[:3]:
            if self.safe_type(field, SAFE_TEXT):
                typed = True

        if not typed:
            return

        try:
            self.driver.hide_keyboard()
        except Exception:
            pass

        buttons = self.find_clickable_elements()

        safe_submit_words = {
            "submit",
            "continue",
            "next",
            "save",
            "done",
            "ok",
        }

        for button in buttons:
            text = self.element_text(button)

            if any(word in text for word in safe_submit_words):
                before = self.page_signature()

                if self.safe_click(button):
                    time.sleep(1)
                    after = self.page_signature()
                    self.detect_error_text()

                    if after and before and after != before:
                        self.metrics.task_completed = 1
                        self.metrics.task_failed = 0
                    elif self.metrics.error_message_present:
                        self.metrics.task_completed = 1
                        self.metrics.task_failed = 0

                    return

        self.metrics.task_completed = 1
        self.metrics.task_failed = 0

    def search_flow(self) -> None:
        self.detect_system_popup()

        field = self.find_search_field()

        if field is None:
            self.metrics.error_count += 1
            return

        before = self.page_signature()

        if not self.safe_type(field, "test"):
            return

        try:
            self.driver.press_keycode(66)
            self.metrics.click_count += 1
        except Exception:
            buttons = self.find_clickable_elements()

            for button in buttons:
                text = self.element_text(button)

                if "search" in text:
                    self.safe_click(button)
                    break

        time.sleep(1)

        after = self.page_signature()
        self.detect_error_text()

        if after and before and after != before:
            self.metrics.task_completed = 1
            self.metrics.task_failed = 0
        elif self.metrics.error_message_present:
            self.metrics.task_completed = 1
            self.metrics.task_failed = 0
        else:
            self.metrics.task_failed = 1

    def run(self) -> AndroidMetrics:
        flow_handlers = {
            "basic_navigation": self.basic_navigation,
            "button_click": self.button_click,
            "form_input": self.form_input,
            "search_flow": self.search_flow,
        }

        handler = flow_handlers[self.flow]

        try:
            handler()

        except TimeoutError:
            self.metrics.timeout_occurred = 1
            self.metrics.error_count += 1
            self.metrics.task_failed = 1

        except WebDriverException as error:
            message = str(error).lower()

            if "anr" in message:
                self.metrics.anr_detected = 1
            elif "crash" in message or "not running" in message:
                self.metrics.crash_detected = 1

            self.metrics.error_count += 1
            self.metrics.task_failed = 1

        except Exception:
            self.metrics.error_count += 1
            self.metrics.task_failed = 1

        self.detect_error_text()
        self.detect_system_popup()

        completion_time = self.elapsed_seconds()

        self.metrics.completion_time = round(
            completion_time,
            3,
        )

        self.metrics.finish_time_ms = round(
            completion_time * 1000,
            2,
        )

        total_problem_count = (
            self.metrics.error_count
            + self.metrics.failed_clicks
            + self.metrics.unnecessary_clicks
            + self.metrics.retry_count
        )

        self.metrics.path_deviation_score = round(
            min(total_problem_count / 10, 1.0),
            3,
        )

        return self.metrics


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description=(
            "Safe generic Appium runner for dummy APKs, "
            "real APKs, and installed Android applications."
        )
    )

    parser.add_argument(
        "--mode",
        required=True,
        choices=[
            "dummy_app",
            "real_apk",
            "installed_app",
        ],
    )

    parser.add_argument("--apk", default="")
    parser.add_argument("--package", default="")
    parser.add_argument("--activity", default="")

    parser.add_argument(
        "--flow",
        required=True,
        choices=[
            "basic_navigation",
            "button_click",
            "form_input",
            "search_flow",
        ],
    )

    parser.add_argument(
        "--device",
        default="emulator-5554",
    )

    parser.add_argument(
        "--testRunId",
        default="manual",
    )

    parser.add_argument(
        "--maxDuration",
        type=int,
        default=60,
    )

    parser.add_argument(
        "--appium-url",
        default="http://127.0.0.1:4723",
    )

    return parser


def validate_arguments(args: argparse.Namespace) -> None:
    if args.maxDuration < 10 or args.maxDuration > 180:
        raise ValueError(
            "--maxDuration must be between 10 and 180."
        )

    if args.mode in {"dummy_app", "real_apk"}:
        if not args.apk:
            raise ValueError(
                "--apk is required for dummy_app and real_apk modes."
            )

        apk_path = Path(args.apk).expanduser().resolve()

        if not apk_path.is_file():
            raise ValueError(
                f"APK file not found: {apk_path}"
            )

        if apk_path.suffix.lower() != ".apk":
            raise ValueError(
                f"File is not an APK: {apk_path}"
            )

    if args.mode == "installed_app" and not args.package:
        raise ValueError(
            "--package is required for installed_app mode."
        )


def create_options(
    args: argparse.Namespace,
) -> UiAutomator2Options:
    options = UiAutomator2Options()

    options.platform_name = "Android"
    options.automation_name = "UiAutomator2"
    options.device_name = args.device
    options.udid = args.device

    options.no_reset = True
    options.new_command_timeout = max(
        args.maxDuration + 30,
        90,
    )

    options.set_capability(
        "appium:autoGrantPermissions",
        False,
    )

    options.set_capability(
        "appium:ignoreHiddenApiPolicyError",
        True,
    )

    if args.mode == "dummy_app":
        options.app = str(
            Path(args.apk).expanduser().resolve()
        )
        options.app_package = (
            args.package or DUMMY_PACKAGE
        )
        options.app_activity = (
            args.activity or DUMMY_ACTIVITY
        )

    elif args.mode == "real_apk":
        options.app = str(
            Path(args.apk).expanduser().resolve()
        )

        if args.package:
            options.app_package = args.package

        if args.activity:
            options.app_activity = args.activity

    elif args.mode == "installed_app":
        options.app_package = args.package

        if args.activity:
            options.app_activity = args.activity

    return options


def output_result(payload: dict[str, Any]) -> None:
    print(
        json.dumps(
            payload,
            ensure_ascii=False,
        ),
        flush=True,
    )


def main() -> int:
    parser = build_parser()
    args = parser.parse_args()

    driver: webdriver.Remote | None = None
    launch_started = time.perf_counter()

    try:
        validate_arguments(args)

        options = create_options(args)

        driver = webdriver.Remote(
            command_executor=args.appium_url,
            options=options,
        )

        launch_time_ms = (
            time.perf_counter() - launch_started
        ) * 1000

        screen_started = time.perf_counter()

        try:
            driver.find_elements(
                AppiumBy.XPATH,
                "//*",
            )
        except Exception:
            pass

        screen_load_time_ms = (
            time.perf_counter() - screen_started
        ) * 1000

        runner = SafeRunner(
            driver=driver,
            flow=args.flow,
            max_duration=args.maxDuration,
        )

        runner.metrics.app_launch_time_ms = round(
            launch_time_ms,
            2,
        )

        runner.metrics.screen_load_time_ms = round(
            screen_load_time_ms,
            2,
        )

        metrics = runner.run()

        status = (
            "success"
            if metrics.task_completed == 1
            else "controlled_failure"
        )

        output_result({
            "status": status,
            "test_run_id": args.testRunId,
            "test_mode": args.mode,
            "message": (
                "Android test completed."
                if status == "success"
                else (
                    "The app opened, but the selected generic "
                    "flow could not be completed safely."
                )
            ),
            "metrics": asdict(metrics),
        })

        return 0

    except ValueError as error:
        output_result({
            "status": "error",
            "test_run_id": args.testRunId,
            "test_mode": args.mode,
            "message": str(error),
            "metrics": None,
        })

        return 2

    except WebDriverException as error:
        message = str(error)

        output_result({
            "status": "error",
            "test_run_id": args.testRunId,
            "test_mode": args.mode,
            "message": (
                "Appium could not start or control the Android app: "
                + message
            ),
            "metrics": None,
        })

        return 3

    except Exception as error:
        output_result({
            "status": "error",
            "test_run_id": args.testRunId,
            "test_mode": args.mode,
            "message": f"Unexpected Android runner error: {error}",
            "metrics": None,
        })

        return 4

    finally:
        if driver is not None:
            try:
                driver.quit()
            except Exception:
                pass


if __name__ == "__main__":
    sys.exit(main())