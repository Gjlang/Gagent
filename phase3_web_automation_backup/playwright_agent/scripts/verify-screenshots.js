const fs = require("fs");
const path = require("path");
const {
  RAW_DATASET_PATH,
  SCREENSHOT_ROOT,
  readCSV,
  getNumber,
  printProblems,
} = require("./verify-utils");

function main() {
  const problems = [];

  if (!fs.existsSync(RAW_DATASET_PATH)) {
    problems.push({ problem: "Raw tracking CSV does not exist", expected_path: RAW_DATASET_PATH });
    printProblems(problems, "", "Screenshot verification failed.");
  }

  if (!fs.existsSync(SCREENSHOT_ROOT)) {
    problems.push({ problem: "Screenshot folder does not exist", expected_path: SCREENSHOT_ROOT });
    printProblems(problems, "", "Screenshot verification failed.");
  }

  const rows = readCSV(RAW_DATASET_PATH);

  rows.forEach((row) => {
    const runId = row.run_id;
    const expectedCount = getNumber(row, "screenshot_count");
    const screenshotDir = path.join(SCREENSHOT_ROOT, runId);

    if (!runId) {
      problems.push({ problem: "Raw row missing run_id", row });
      return;
    }

    if (!fs.existsSync(screenshotDir)) {
      problems.push({ run_id: runId, problem: "Screenshot folder missing", expected_path: screenshotDir });
      return;
    }

    const pngFiles = fs.readdirSync(screenshotDir).filter((file) => file.endsWith(".png"));

    if (pngFiles.length === 0) {
      problems.push({ run_id: runId, problem: "No PNG screenshots found" });
    }

    if (pngFiles.length !== expectedCount) {
      problems.push({
        run_id: runId,
        problem: "Screenshot count mismatch",
        csv_screenshot_count: expectedCount,
        actual_png_count: pngFiles.length,
      });
    }

    if (!pngFiles.some((file) => file.includes("page_load"))) {
      problems.push({ run_id: runId, problem: "Missing page load screenshot" });
    }

    if (!pngFiles.some((file) => file.includes("success") || file.includes("error"))) {
      problems.push({ run_id: runId, problem: "Missing success or error screenshot" });
    }
  });

  printProblems(problems, "Screenshot verification passed.", "Screenshot verification failed.");

  console.log("Raw rows checked:", rows.length);
  console.log("Screenshot folder:", SCREENSHOT_ROOT);
}

main();
