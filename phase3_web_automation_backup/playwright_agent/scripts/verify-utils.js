const fs = require("fs");
const path = require("path");

const ROOT = path.join(__dirname, "..");
const OUTPUT_ROOT = path.join(ROOT, "outputs");
const DATASET_ROOT = path.join(OUTPUT_ROOT, "datasets");
const FINAL_EXPORT_ROOT = path.join(OUTPUT_ROOT, "final_export");
const LOG_ROOT = path.join(OUTPUT_ROOT, "logs");
const SCREENSHOT_ROOT = path.join(OUTPUT_ROOT, "screenshots");

const RAW_DATASET_PATH = path.join(DATASET_ROOT, "agent_generated_ux_dataset.csv");
const OLD_RAW_DATASET_PATH = path.join(DATASET_ROOT, "agent_generated_ux_dataset_old.csv");
const OLD_FINAL_DATASET_PATH = path.join(FINAL_EXPORT_ROOT, "agent_generated_ux_dataset.csv");
const FINAL_DATASET_PATH = path.join(FINAL_EXPORT_ROOT, "ux_friction_dataset.csv");

const FINAL_COLUMNS = [
  "source_dataset",
  "flow_type",
  "completion_time",
  "click_count",
  "scroll_count",
  "keyboard_count",
  "retry_count",
  "error_count",
  "failed_clicks",
  "feedback_delay",
  "task_completed",
  "screenshot_count",
  "error_message_clarity",
  "friction_level",
];

const RAW_COLUMNS = [
  "run_id",
  "source_dataset",
  "flow_type",
  "page_url",
  "friction_scenario",
  ...FINAL_COLUMNS.filter((column) => column !== "source_dataset" && column !== "flow_type"),
];

const NUMERIC_COLUMNS = [
  "completion_time",
  "click_count",
  "scroll_count",
  "keyboard_count",
  "retry_count",
  "error_count",
  "failed_clicks",
  "feedback_delay",
  "task_completed",
  "screenshot_count",
  "error_message_clarity",
];

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function parseCSVLine(line) {
  const values = [];
  let current = "";
  let insideQuotes = false;

  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];
    const nextChar = line[index + 1];

    if (char === '"' && insideQuotes && nextChar === '"') {
      current += '"';
      index += 1;
    } else if (char === '"') {
      insideQuotes = !insideQuotes;
    } else if (char === "," && !insideQuotes) {
      values.push(current);
      current = "";
    } else {
      current += char;
    }
  }

  values.push(current);
  return values;
}

function readCSV(filePath) {
  if (!fs.existsSync(filePath)) {
    return [];
  }

  const content = fs.readFileSync(filePath, "utf-8").trim();
  if (!content) return [];

  const lines = content.split(/\r?\n/).filter(Boolean);
  const headers = parseCSVLine(lines[0]);

  return lines.slice(1).map((line) => {
    const values = parseCSVLine(line);
    const row = {};

    headers.forEach((header, index) => {
      row[header] = values[index] ?? "";
    });

    return row;
  });
}

function csvEscape(value) {
  if (value === null || value === undefined) return "";
  const text = String(value);
  if (text.includes(",") || text.includes("\n") || text.includes('"')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

function writeCSV(filePath, columns, rows) {
  ensureDir(path.dirname(filePath));
  const content = [
    columns.join(","),
    ...rows.map((row) => columns.map((column) => csvEscape(row[column])).join(",")),
  ].join("\n");
  fs.writeFileSync(filePath, `${content}\n`);
}

function getNumber(row, column, fallback = 0) {
  const value = Number(row[column]);
  return Number.isFinite(value) ? value : fallback;
}

function groupBy(rows, key) {
  return rows.reduce((groups, row) => {
    const value = row[key] || "Unknown";
    groups[value] = groups[value] || [];
    groups[value].push(row);
    return groups;
  }, {});
}

function average(values) {
  const validValues = values.map(Number).filter(Number.isFinite);
  if (validValues.length === 0) return 0;
  return validValues.reduce((sum, value) => sum + value, 0) / validValues.length;
}

function printProblems(problems, successMessage, failureMessage) {
  if (problems.length > 0) {
    console.error(`\n${failureMessage}`);
    console.error(JSON.stringify(problems, null, 2));
    process.exit(1);
  }

  console.log(successMessage);
}

module.exports = {
  ROOT,
  OUTPUT_ROOT,
  DATASET_ROOT,
  FINAL_EXPORT_ROOT,
  LOG_ROOT,
  SCREENSHOT_ROOT,
  RAW_DATASET_PATH,
  OLD_RAW_DATASET_PATH,
  OLD_FINAL_DATASET_PATH,
  FINAL_DATASET_PATH,
  FINAL_COLUMNS,
  RAW_COLUMNS,
  NUMERIC_COLUMNS,
  ensureDir,
  readCSV,
  writeCSV,
  getNumber,
  groupBy,
  average,
  printProblems,
};
