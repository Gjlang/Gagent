const fs = require("fs");
const {
  RAW_DATASET_PATH,
  OLD_RAW_DATASET_PATH,
  OLD_FINAL_DATASET_PATH,
  FINAL_DATASET_PATH,
  FINAL_COLUMNS,
  NUMERIC_COLUMNS,
  readCSV,
  writeCSV,
  getNumber,
} = require("./verify-utils");

const validFrictionLevels = new Set(["Low", "Medium", "High"]);
const validErrorClarityValues = new Set([2, 1, 0, -1]);

function chooseInputPath() {
  if (fs.existsSync(RAW_DATASET_PATH)) return RAW_DATASET_PATH;
  if (fs.existsSync(OLD_RAW_DATASET_PATH)) return OLD_RAW_DATASET_PATH;
  if (fs.existsSync(OLD_FINAL_DATASET_PATH)) return OLD_FINAL_DATASET_PATH;
  return null;
}

function mapFrictionLevel(row) {
  if (validFrictionLevels.has(row.friction_level)) return row.friction_level;
  if (validFrictionLevels.has(row.expected_friction_level)) return row.expected_friction_level;

  const scenario = String(row.friction_scenario || "").toLowerCase();
  if (scenario === "good") return "Low";
  if (scenario === "medium") return "Medium";
  if (scenario === "bad") return "High";

  return "Unknown";
}

function normalizeNumeric(row, column) {
  const defaultValue = column === "error_message_clarity" ? -1 : 0;
  const value = getNumber(row, column, defaultValue);

  if (column === "task_completed") {
    return value === 1 ? 1 : 0;
  }

  if (column === "error_message_clarity") {
    return validErrorClarityValues.has(value) ? value : -1;
  }

  return value < 0 && column !== "feedback_delay" ? 0 : value;
}

function normalizeRow(row) {
  const normalized = {
    source_dataset: row.source_dataset || row.source || "playwright_dummy_website",
    flow_type: row.flow_type || "unknown",
    friction_level: mapFrictionLevel(row),
  };

  NUMERIC_COLUMNS.forEach((column) => {
    normalized[column] = normalizeNumeric(row, column);
  });

  return normalized;
}

function validateRows(rows) {
  const problems = [];

  rows.forEach((row, index) => {
    FINAL_COLUMNS.forEach((column) => {
      if (!(column in row)) {
        problems.push({ row: index + 1, problem: `Missing column ${column}` });
      }
    });

    NUMERIC_COLUMNS.forEach((column) => {
      if (!Number.isFinite(Number(row[column]))) {
        problems.push({ row: index + 1, column, problem: "Numeric value is invalid", value: row[column] });
      }
    });

    if (!validFrictionLevels.has(row.friction_level)) {
      problems.push({ row: index + 1, problem: "Invalid friction_level", value: row.friction_level });
    }
  });

  return problems;
}

function main() {
  const inputPath = chooseInputPath();

  if (!inputPath) {
    console.error("No raw Playwright dataset found. Run Playwright tests first.");
    process.exit(1);
  }

  const rawRows = readCSV(inputPath);

  if (rawRows.length === 0) {
    console.error(`Input dataset is empty: ${inputPath}`);
    process.exit(1);
  }

  const normalizedRows = rawRows.map(normalizeRow);
  const problems = validateRows(normalizedRows);

  if (problems.length > 0) {
    console.error("Final dataset export failed validation:");
    console.error(JSON.stringify(problems.slice(0, 30), null, 2));
    process.exit(1);
  }

  writeCSV(FINAL_DATASET_PATH, FINAL_COLUMNS, normalizedRows);

  console.log("Final standardized dataset exported successfully.");
  console.log(`Input: ${inputPath}`);
  console.log(`Output: ${FINAL_DATASET_PATH}`);
  console.log(`Rows exported: ${normalizedRows.length}`);
  console.log(`Columns exported: ${FINAL_COLUMNS.length}`);
}

main();
