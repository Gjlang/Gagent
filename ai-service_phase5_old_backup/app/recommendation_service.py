from typing import Any, Dict, List


def generate_recommendations(features: Dict[str, Any]) -> List[str]:
    recommendations: List[str] = []

    completion_time = float(features.get("completion_time", 0))
    click_count = int(features.get("click_count", 0))
    scroll_count = int(features.get("scroll_count", 0))
    retry_count = int(features.get("retry_count", 0))
    error_count = int(features.get("error_count", 0))
    failed_clicks = int(features.get("failed_clicks", 0))
    feedback_delay = float(features.get("feedback_delay", 0))
    task_completed = int(features.get("task_completed", -1))
    error_message_clarity = int(features.get("error_message_clarity", -1))

    if task_completed == 0:
        recommendations.append("Task failure detected. Review the user flow.")

    if retry_count >= 2:
        recommendations.append("High retry count detected. Review button labels or form instructions.")

    if feedback_delay >= 1000:
        recommendations.append("High feedback delay detected. Improve response time or add a loading indicator.")

    if failed_clicks >= 1:
        recommendations.append("Failed clicks detected. Check whether buttons are visible, clickable, and correctly positioned.")

    if error_count >= 1:
        recommendations.append("Errors detected. Improve form validation and error handling.")

    if error_message_clarity == 0:
        recommendations.append("Low error message clarity detected. Improve error message wording and guidance.")

    if scroll_count >= 5:
        recommendations.append("High scroll count detected. Move important CTA or key information higher on the page.")

    if completion_time >= 10000:
        recommendations.append("Long completion time detected. Simplify the task flow and reduce unnecessary steps.")

    if click_count >= 12:
        recommendations.append("High click count detected. Reduce interaction steps and improve navigation clarity.")

    if not recommendations:
        recommendations.append("No major UX friction indicator detected from the submitted metrics.")

    return recommendations