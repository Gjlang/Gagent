import argparse
import os
import time
import pandas as pd

from appium import webdriver
from appium.options.android import UiAutomator2Options
from selenium.common.exceptions import WebDriverException
from selenium.webdriver.common.by import By

from metrics_collector import AndroidUXMetrics, MetricsTimer, classify_error_clarity


PACKAGE_NAME = "com.gagent.dummyandroid"


def rid(element_id: str) -> str:
    return f"{PACKAGE_NAME}:id/{element_id}"


def find(driver, element_id: str, timeout: float = 5):
    end = time.time() + timeout
    last_error = None

    while time.time() < end:
        try:
            return driver.find_element(By.ID, rid(element_id))
        except Exception as exc:
            last_error = exc
            time.sleep(0.2)

    raise last_error


def safe_find(driver, element_id: str):
    try:
        return driver.find_element(By.ID, rid(element_id))
    except Exception:
        return None


def click_element(driver, metrics: AndroidUXMetrics, element_id: str, timeout: float = 5):
    try:
        el = find(driver, element_id, timeout)
        el.click()
        metrics.click_count += 1
        return True
    except Exception:
        metrics.failed_clicks += 1
        metrics.error_count += 1
        return False


def type_text(driver, metrics: AndroidUXMetrics, element_id: str, text: str):
    try:
        el = find(driver, element_id)
        el.clear()
        if text:
            el.send_keys(text)
            metrics.keyboard_count += 1
        return True
    except Exception:
        metrics.error_count += 1
        return False


def select_scenario(driver, scenario: str):
    spinner = find(driver, "scenario_spinner")
    spinner.click()
    time.sleep(0.3)

    option = driver.find_element(By.XPATH, f"//*[@text='{scenario}']")
    option.click()
    time.sleep(0.3)


def collect_messages(driver, metrics: AndroidUXMetrics):
    error = safe_find(driver, "error_message")
    success = safe_find(driver, "success_message")

    error_text = ""
    success_text = ""

    if error:
        error_text = error.text.strip()

    if success:
        success_text = success.text.strip()

    if error_text:
        metrics.error_message_present = 1
        metrics.error_message_clarity = classify_error_clarity(error_text)
        metrics.error_count += 1

    if success_text:
        metrics.task_completed = 1

    return error_text, success_text


def detect_popup(driver, metrics: AndroidUXMetrics):
    popup = safe_find(driver, "popup_modal")

    if popup:
        metrics.popup_detected = 1
        metrics.overlay_blocks_action = 1

        close = safe_find(driver, "popup_close_button")
        if close:
            close.click()
            metrics.click_count += 1

        time.sleep(0.5)


def scroll_down(driver, metrics: AndroidUXMetrics):
    size = driver.get_window_size()
    x = size["width"] // 2
    start_y = int(size["height"] * 0.75)
    end_y = int(size["height"] * 0.25)

    driver.swipe(x, start_y, x, end_y, 500)
    metrics.scroll_count += 1
    time.sleep(0.5)


def run_flow(driver, flow: str, scenario: str, app_launch_time_ms: int):
    metrics = AndroidUXMetrics(
        flow_type=flow,
        scenario_type=scenario,
        app_launch_time_ms=app_launch_time_ms
    )

    timer = MetricsTimer()

    try:
        select_scenario(driver, scenario)

        flow_button_map = {
            "login": "flow_login_button",
            "signup": "flow_signup_button",
            "search": "flow_search_button",
            "button_click": "flow_button_click_button",
            "form_submit": "flow_form_submit_button"
        }

        screen_timer = MetricsTimer()
        click_element(driver, metrics, flow_button_map[flow])
        time.sleep(0.5)

        if scenario == "medium":
            time.sleep(1.0)
        elif scenario == "bad":
            time.sleep(2.0)

        metrics.screen_load_time_ms = screen_timer.elapsed_ms()

        detect_popup(driver, metrics)

        interaction_timer = MetricsTimer()

        if flow == "login":
            if scenario == "good":
                type_text(driver, metrics, "login_email_input", "user@test.com")
                type_text(driver, metrics, "login_password_input", "Password123")
            elif scenario == "medium":
                type_text(driver, metrics, "login_email_input", "medium@test.com")
                type_text(driver, metrics, "login_password_input", "Password123")
            else:
                metrics.retry_count += 1
                type_text(driver, metrics, "login_password_input", "wrong")

            click_element(driver, metrics, "login_submit_button")

        elif flow == "signup":
            if scenario == "good":
                type_text(driver, metrics, "signup_name_input", "Test User")
                type_text(driver, metrics, "signup_email_input", "test@example.com")
                click_element(driver, metrics, "signup_submit_button")
            elif scenario == "medium":
                type_text(driver, metrics, "signup_name_input", "Test User")
                type_text(driver, metrics, "signup_email_input", "bad-email")
                click_element(driver, metrics, "signup_submit_button")
            else:
                type_text(driver, metrics, "signup_name_input", "Blocked User")
                type_text(driver, metrics, "signup_email_input", "blocked@example.com")
                clicked = click_element(driver, metrics, "signup_submit_button", timeout=2)
                if not clicked:
                    metrics.failed_clicks += 1
                metrics.task_failed = 1

        elif flow == "search":
            if scenario == "good":
                type_text(driver, metrics, "search_input", "laptop")
            elif scenario == "medium":
                type_text(driver, metrics, "search_input", "phone")
            else:
                type_text(driver, metrics, "search_input", "unknown")
                metrics.path_deviation_score = 1

            click_element(driver, metrics, "search_submit_button")

        elif flow == "button_click":
            if scenario == "good":
                click_element(driver, metrics, "main_cta_button")
            elif scenario == "medium":
                click_element(driver, metrics, "main_cta_button")
            else:
                click_element(driver, metrics, "main_cta_button")
                metrics.anr_detected = 1
                metrics.timeout_occurred = 1

                scroll_down(driver, metrics)
                click_element(driver, metrics, "hidden_scroll_button", timeout=2)

                small_btn = safe_find(driver, "small_tap_button")
                if small_btn:
                    try:
                        small_btn.click()
                        metrics.click_count += 1
                    except Exception:
                        metrics.failed_clicks += 1

        elif flow == "form_submit":
            if scenario == "good":
                type_text(driver, metrics, "form_name_input", "Test User")
                type_text(driver, metrics, "form_email_input", "test@example.com")
            elif scenario == "medium":
                type_text(driver, metrics, "form_name_input", "")
                type_text(driver, metrics, "form_email_input", "bad-email")
                metrics.retry_count += 1
            else:
                type_text(driver, metrics, "form_name_input", "")
                type_text(driver, metrics, "form_email_input", "")
                metrics.retry_count += 1
                metrics.timeout_occurred = 1

            click_element(driver, metrics, "form_submit_button")

        if scenario == "medium":
            time.sleep(1.3)
        elif scenario == "bad":
            time.sleep(4.3)
        else:
            time.sleep(0.7)

        metrics.interaction_response_time_ms = interaction_timer.elapsed_ms()
        metrics.feedback_delay_ms = metrics.interaction_response_time_ms

        error_text, success_text = collect_messages(driver, metrics)

        if not metrics.task_completed:
            if scenario == "good":
                metrics.task_failed = 1
            elif error_text:
                metrics.task_failed = 1

        if scenario == "bad" and flow in ["login", "form_submit"]:
            metrics.timeout_occurred = 1

        metrics.completion_time = timer.elapsed_seconds()
        metrics.finish_time_ms = timer.elapsed_ms()

    except WebDriverException:
        metrics.crash_detected = 1
        metrics.task_failed = 1
        metrics.error_count += 1
        metrics.completion_time = timer.elapsed_seconds()
        metrics.finish_time_ms = timer.elapsed_ms()

    except Exception:
        metrics.task_failed = 1
        metrics.error_count += 1
        metrics.completion_time = timer.elapsed_seconds()
        metrics.finish_time_ms = timer.elapsed_ms()

    finally:
        try:
            driver.terminate_app(PACKAGE_NAME)
            time.sleep(0.3)
            driver.activate_app(PACKAGE_NAME)
            time.sleep(0.8)
        except Exception:
            pass

    return metrics.to_dict()


def create_driver(app_path: str):
    options = UiAutomator2Options()
    options.platform_name = "Android"
    options.automation_name = "UiAutomator2"
    options.app = os.path.abspath(app_path)
    options.app_package = PACKAGE_NAME
    options.app_activity = ".MainActivity"
    options.no_reset = False
    options.new_command_timeout = 120

    launch_timer = MetricsTimer()
    driver = webdriver.Remote("http://127.0.0.1:4723", options=options)
    app_launch_time_ms = launch_timer.elapsed_ms()

    time.sleep(1.0)
    return driver, app_launch_time_ms


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--app", required=True, help="Path to app-debug.apk")
    parser.add_argument("--repeat", type=int, default=1)
    parser.add_argument("--out", default="outputs/android_appium_metrics.csv")
    args = parser.parse_args()

    flows = ["login", "signup", "search", "button_click", "form_submit"]
    scenarios = ["good", "medium", "bad"]

    rows = []

    output_dir = os.path.dirname(args.out)
    if output_dir:
        os.makedirs(output_dir, exist_ok=True)

    for i in range(args.repeat):
        print(f"Repeat {i + 1}/{args.repeat}")

        driver = None

        try:
            driver, app_launch_time_ms = create_driver(args.app)

            for flow in flows:
                for scenario in scenarios:
                    print(f"Running {flow} - {scenario}")

                    row = run_flow(
                        driver=driver,
                        flow=flow,
                        scenario=scenario,
                        app_launch_time_ms=app_launch_time_ms
                    )

                    row["run_index"] = i + 1
                    rows.append(row)

                    pd.DataFrame(rows).to_csv(args.out, index=False)

        except Exception as exc:
            print(f"Repeat {i + 1} failed: {exc}")

        finally:
            if driver:
                try:
                    driver.quit()
                except Exception as exc:
                    print("Warning: driver.quit() failed during cleanup.")
                    print(f"Details: {exc}")

        time.sleep(1.0)

    print(f"Saved dataset: {args.out}")
    print(f"Total rows: {len(rows)}")

if __name__ == "__main__":
    main()