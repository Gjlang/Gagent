const fs = require("fs");
const path = require("path");
const {
  RAW_DATASET_PATH,
  LOG_ROOT,
  readCSV,
  getNumber,
  printProblems,
} = require("./verify-utils");

function main() {
  const problems = [];

  if (!fs.existsSync(RAW_DATASET_PATH)) {
    problems.push({ problem: "Raw tracking CSV does not exist", expected_path: RAW_DATASET_PATH });
    printProblems(problems, "", "Log verification failed.");
  }

  if (!fs.existsSync(LOG_ROOT)) {
    problems.push({ problem: "Log folder does not exist", expected_path: LOG_ROOT });
    printProblems(problems, "", "Log verification failed.");
  }

  const rows = readCSV(RAW_DATASET_PATH);

  rows.forEach((row) => {
    const runId = row.run_id;
    const logPath = path.join(LOG_ROOT, `${runId}.json`);

    if (!runId) {
      problems.push({ problem: "Raw row missing run_id", row });
      return;
    }

    if (!fs.existsSync(logPath)) {
      problems.push({ run_id: runId, problem: "Missing JSON log file", expected_path: logPath });
      return;
    }

    let logs = [];
    try {
      logs = JSON.parse(fs.readFileSync(logPath, "utf-8"));
    } catch (error) {
      problems.push({ run_id: runId, problem: "Log file is not valid JSON", error: error.message });
      return;
    }

    if (!Array.isArray(logs) || logs.length === 0) {
      problems.push({ run_id: runId, problem: "Log file has no entries" });
      return;
    }

    const hasNavigate = logs.some((entry) => entry.action_type === "navigate");
    const hasScreenshot = logs.some((entry) => entry.action_type === "screenshot");
    const failedLogCount = logs.filter((entry) => entry.action_status === "failed").length;

    if (!hasNavigate) problems.push({ run_id: runId, problem: "Log missing navigate action" });
    if (!hasScreenshot) problems.push({ run_id: runId, problem: "Log missing screenshot action" });

    if (getNumber(row, "failed_clicks") > 0 && failedLogCount === 0) {
      problems.push({ run_id: runId, problem: "failed_clicks > 0 but log has no failed actions" });
    }
  });

  printProblems(problems, "Log verification passed.", "Log verification failed.");

  console.log("Raw rows checked:", rows.length);
  console.log("Log folder:", LOG_ROOT);
}

main();
