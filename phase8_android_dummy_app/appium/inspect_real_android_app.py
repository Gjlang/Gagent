import argparse
import time
from pathlib import Path
from xml.etree import ElementTree

from appium import webdriver
from appium.options.android import UiAutomator2Options


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Capture the visible UI hierarchy of a real Android APK."
    )

    parser.add_argument("--app", required=True)
    parser.add_argument("--package", required=True)
    parser.add_argument("--activity", required=True)
    parser.add_argument("--device", default="emulator-5554")
    parser.add_argument("--out-dir", default="outputs/inspection")
    parser.add_argument("--wait", type=float, default=8)
    parser.add_argument(
        "--appium-url",
        default="http://127.0.0.1:4723",
    )

    args = parser.parse_args()

    output_dir = Path(args.out_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    options = UiAutomator2Options()
    options.platform_name = "Android"
    options.automation_name = "UiAutomator2"
    options.device_name = args.device
    options.udid = args.device
    options.app = str(Path(args.app).resolve())
    options.app_package = args.package
    options.app_activity = args.activity
    options.no_reset = False
    options.full_reset = False
    options.new_command_timeout = 180

    options.set_capability(
        "appium:autoGrantPermissions",
        True,
    )

    driver = webdriver.Remote(
        args.appium_url,
        options=options,
    )

    try:
        print(
            f"Waiting {args.wait} seconds. "
            "You may navigate manually on the emulator now."
        )

        time.sleep(args.wait)

        source = driver.page_source

        source_path = output_dir / "page-source.xml"
        screenshot_path = output_dir / "screen.png"
        elements_path = output_dir / "visible-elements.txt"

        source_path.write_text(
            source,
            encoding="utf-8",
        )

        driver.save_screenshot(
            str(screenshot_path)
        )

        root = ElementTree.fromstring(source)
        lines = []

        for node in root.iter():
            attributes = node.attrib

            useful = {
                "class": attributes.get("class", ""),
                "text": attributes.get("text", ""),
                "content-desc": attributes.get(
                    "content-desc",
                    "",
                ),
                "resource-id": attributes.get(
                    "resource-id",
                    "",
                ),
                "clickable": attributes.get(
                    "clickable",
                    "",
                ),
                "bounds": attributes.get(
                    "bounds",
                    "",
                ),
            }

            if (
                useful["text"]
                or useful["content-desc"]
                or useful["resource-id"]
            ):
                lines.append(
                    " | ".join(
                        f"{key}={value}"
                        for key, value in useful.items()
                    )
                )

        elements_path.write_text(
            "\n".join(lines),
            encoding="utf-8",
        )

        print(f"Screenshot: {screenshot_path}")
        print(f"Page source: {source_path}")
        print(f"Visible elements: {elements_path}")

    finally:
        driver.quit()


if __name__ == "__main__":
    main()