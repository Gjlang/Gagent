const fs = require("fs");
const {
  FINAL_DATASET_PATH,
  FINAL_COLUMNS,
  NUMERIC_COLUMNS,
  readCSV,
  getNumber,
  groupBy,
  printProblems,
} = require("./verify-utils");

const validFrictionLevels = new Set(["Low", "Medium", "High"]);
const validErrorClarityValues = new Set([2, 1, 0, -1]);

function main() {
  const problems = [];

  if (!fs.existsSync(FINAL_DATASET_PATH)) {
    problems.push({ problem: "Final CSV does not exist", expected_path: FINAL_DATASET_PATH });
    printProblems(problems, "", "Metric verification failed.");
  }

  const rows = readCSV(FINAL_DATASET_PATH);

  if (rows.length === 0) {
    problems.push({ problem: "Final CSV exists but has zero rows" });
  }

  const actualColumns = rows.length > 0 ? Object.keys(rows[0]) : [];

  if (actualColumns.length !== 14) {
    problems.push({ problem: "Final CSV must have exactly 14 columns", actual_count: actualColumns.length });
  }

  FINAL_COLUMNS.forEach((column) => {
    if (!actualColumns.includes(column)) {
      problems.push({ problem: "Required column missing", column });
    }
  });

  rows.forEach((row, index) => {
    NUMERIC_COLUMNS.forEach((column) => {
      const value = Number(row[column]);
      if (!Number.isFinite(value)) {
        problems.push({ row: index + 1, column, problem: "Numeric value is invalid", value: row[column] });
      }
    });

    if (!validFrictionLevels.has(row.friction_level)) {
      problems.push({ row: index + 1, problem: "Invalid friction_level", value: row.friction_level });
    }

    if (!validErrorClarityValues.has(getNumber(row, "error_message_clarity", -999))) {
      problems.push({ row: index + 1, problem: "Invalid error_message_clarity", value: row.error_message_clarity });
    }

    if (![0, 1].includes(getNumber(row, "task_completed", -1))) {
      problems.push({ row: index + 1, problem: "task_completed must be 0 or 1", value: row.task_completed });
    }
  });

  const levels = groupBy(rows, "friction_level");
  ["Low", "Medium", "High"].forEach((level) => {
    if (!levels[level] || levels[level].length === 0) {
      problems.push({ problem: "Missing friction class", friction_level: level });
    }
  });

  const keyboardTotal = rows.reduce((sum, row) => sum + getNumber(row, "keyboard_count"), 0);
  if (keyboardTotal === 0) {
    problems.push({ problem: "keyboard_count is always zero" });
  }

  const clarityValues = new Set(rows.map((row) => getNumber(row, "error_message_clarity", -999)));
  if (![2, 1, 0, -1].some((value) => clarityValues.has(value))) {
    problems.push({ problem: "No valid error_message_clarity values found" });
  }

  printProblems(problems, "Metric verification passed.", "Metric verification failed.");

  console.log("Rows:", rows.length);
  console.log("Columns:", actualColumns.length);
  console.log("Class counts:", Object.fromEntries(Object.entries(levels).map(([key, value]) => [key, value.length])));
  console.log("keyboard_count total:", keyboardTotal);
  console.log("error_message_clarity values:", [...clarityValues].sort((a, b) => a - b));
}

main();
