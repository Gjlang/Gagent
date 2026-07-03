from typing import Any, Dict, List


def _get_number(input_data: Dict[str, Any], key: str, default: float = 0.0) -> float:
    value = input_data.get(key, default)
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def generate_recommendations(
    input_data: Dict[str, Any],
    predicted_friction_level: str,
) -> List[str]:
    recommendations: List[str] = []

    feedback_delay_ms = _get_number(input_data, "feedback_delay_ms")
    page_load_time_ms = _get_number(input_data, "page_load_time_ms")
    cumulative_layout_shift = _get_number(input_data, "cumulative_layout_shift")
    failed_clicks = _get_number(input_data, "failed_clicks")
    error_count = _get_number(input_data, "error_count")
    retry_count = _get_number(input_data, "retry_count")
    path_deviation_score = _get_number(input_data, "path_deviation_score")

    popup_detected = int(_get_number(input_data, "popup_detected"))
    cookie_banner_detected = int(_get_number(input_data, "cookie_banner_detected"))
    overlay_blocks_cta = int(_get_number(input_data, "overlay_blocks_cta"))
    error_message_present = int(_get_number(input_data, "error_message_present"))
    error_message_clarity = int(_get_number(input_data, "error_message_clarity", -1))
    task_completed = int(_get_number(input_data, "task_completed", 1))

    if page_load_time_ms >= 3000:
        recommendations.append("Improve page loading performance.")

    if feedback_delay_ms >= 1000:
        recommendations.append("Reduce response delay after user actions.")

    if cumulative_layout_shift >= 0.25:
        recommendations.append("Reduce layout movement after page load.")

    if failed_clicks >= 2:
        recommendations.append("Review button visibility, clickability, and target size.")

    if popup_detected == 1:
        recommendations.append("Avoid interrupting the task with popup overlays.")

    if cookie_banner_detected == 1:
        recommendations.append("Ensure cookie banners do not block the main user journey.")

    if overlay_blocks_cta == 1:
        recommendations.append("Ensure overlays do not block the main CTA.")

    if error_count >= 2:
        recommendations.append("Review form validation, broken interactions, or task-blocking errors.")

    if retry_count >= 2:
        recommendations.append("Reduce the need for repeated user attempts.")

    if path_deviation_score >= 0.5:
        recommendations.append("Simplify the navigation path and reduce unnecessary detours.")

    if error_message_present == 1 and error_message_clarity in {0, 1}:
        recommendations.append("Improve error message clarity so users know how to recover.")

    if task_completed == 0:
        recommendations.append("Investigate why the task could not be completed.")

    if predicted_friction_level == "High" and not recommendations:
        recommendations.append("Review the full user journey because the model detected high UX friction.")

    if predicted_friction_level == "Medium" and not recommendations:
        recommendations.append("Review moderate friction points in the journey before they become blockers.")

    if not recommendations:
        recommendations.append("No major UX issue detected from the submitted interaction metrics.")

    return recommendations