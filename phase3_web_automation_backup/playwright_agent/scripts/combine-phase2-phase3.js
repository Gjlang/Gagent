const fs = require("fs");
const path = require("path");

const REQUIRED_COLUMNS = [
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

const VALID_FRICTION_LEVELS = ["Low", "Medium", "High"];

const phase2Path = path.join(
  __dirname,
  "../../../phase2_dataset_preparation/outputs/processed/feature_engineered_interaction_dataset.csv",
);

const phase3Path = path.join(
  __dirname,
  "../outputs/final_export/ux_friction_dataset.csv",
);

const outputPath = path.join(
  __dirname,
  "../outputs/final_export/combined_ux_friction_dataset.csv",
);

function parseCsvLine(line) {
  const result = [];
  let current = "";
  let insideQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];

    if (char === '"' && insideQuotes && nextChar === '"') {
      current += '"';
      i++;
    } else if (char === '"') {
      insideQuotes = !insideQuotes;
    } else if (char === "," && !insideQuotes) {
      result.push(current.trim());
      current = "";
    } else {
      current += char;
    }
  }

  result.push(current.trim());
  return result;
}

function escapeCsvValue(value) {
  const text = String(value ?? "");

  if (text.includes(",") || text.includes('"') || text.includes("\n")) {
    return `"${text.replace(/"/g, '""')}"`;
  }

  return text;
}

function readCsv(filePath, datasetName) {
  if (!fs.existsSync(filePath)) {
    throw new Error(`${datasetName} file not found: ${filePath}`);
  }

  const content = fs.readFileSync(filePath, "utf8").trim();

  if (!content) {
    throw new Error(`${datasetName} file is empty: ${filePath}`);
  }

  const lines = content.split(/\r?\n/).filter((line) => line.trim() !== "");
  const header = parseCsvLine(lines[0]);

  const rows = lines.slice(1).map((line, index) => {
    const values = parseCsvLine(line);

    if (values.length !== header.length) {
      throw new Error(
        `${datasetName} row ${index + 2} has ${values.length} values but header has ${header.length} columns.`,
      );
    }

    const row = {};
    header.forEach((column, columnIndex) => {
      row[column] = values[columnIndex];
    });

    return row;
  });

  return {
    header,
    rows,
  };
}

function validateHeader(header, datasetName) {
  const missingColumns = REQUIRED_COLUMNS.filter(
    (column) => !header.includes(column),
  );
  const extraColumns = header.filter(
    (column) => !REQUIRED_COLUMNS.includes(column),
  );

  if (header.length !== REQUIRED_COLUMNS.length) {
    throw new Error(
      `${datasetName} must have exactly ${REQUIRED_COLUMNS.length} columns, but found ${header.length}.`,
    );
  }

  if (missingColumns.length > 0) {
    throw new Error(
      `${datasetName} is missing columns: ${missingColumns.join(", ")}`,
    );
  }

  if (extraColumns.length > 0) {
    throw new Error(
      `${datasetName} has extra columns: ${extraColumns.join(", ")}`,
    );
  }

  const sameOrder = REQUIRED_COLUMNS.every(
    (column, index) => header[index] === column,
  );

  if (!sameOrder) {
    throw new Error(
      `${datasetName} columns exist but are not in the required order.\nRequired: ${REQUIRED_COLUMNS.join(
        ", ",
      )}\nFound: ${header.join(", ")}`,
    );
  }
}

function validateRows(rows, datasetName) {
  if (rows.length === 0) {
    throw new Error(`${datasetName} has zero data rows.`);
  }

  const problems = [];

  rows.forEach((row, index) => {
    const rowNumber = index + 2;

    NUMERIC_COLUMNS.forEach((column) => {
      const value = row[column];

      if (value === undefined || value === "") {
        problems.push(`${datasetName} row ${rowNumber}: ${column} is empty.`);
        return;
      }

      const numberValue = Number(value);

      if (Number.isNaN(numberValue)) {
        problems.push(
          `${datasetName} row ${rowNumber}: ${column} is not numeric: ${value}`,
        );
      }
    });

    if (!VALID_FRICTION_LEVELS.includes(row.friction_level)) {
      problems.push(
        `${datasetName} row ${rowNumber}: invalid friction_level: ${row.friction_level}`,
      );
    }

    if (!row.source_dataset || row.source_dataset.trim() === "") {
      problems.push(
        `${datasetName} row ${rowNumber}: source_dataset is empty.`,
      );
    }

    if (!row.flow_type || row.flow_type.trim() === "") {
      problems.push(`${datasetName} row ${rowNumber}: flow_type is empty.`);
    }
  });

  if (problems.length > 0) {
    console.error("\nValidation problems found:");
    problems.slice(0, 30).forEach((problem) => console.error(`- ${problem}`));

    if (problems.length > 30) {
      console.error(`...and ${problems.length - 30} more problem(s).`);
    }

    throw new Error(`${datasetName} row validation failed.`);
  }
}

function countByColumn(rows, columnName) {
  return rows.reduce((counts, row) => {
    const value = row[columnName] || "UNKNOWN";
    counts[value] = (counts[value] || 0) + 1;
    return counts;
  }, {});
}

function writeCsv(filePath, rows) {
  const lines = [];

  lines.push(REQUIRED_COLUMNS.join(","));

  rows.forEach((row) => {
    const values = REQUIRED_COLUMNS.map((column) =>
      escapeCsvValue(row[column]),
    );
    lines.push(values.join(","));
  });

  fs.mkdirSync(path.dirname(filePath), { recursive: true });
  fs.writeFileSync(filePath, `${lines.join("\n")}\n`, "utf8");
}

function main() {
  console.log("Combining Phase 2 and Phase 3 datasets...\n");

  console.log("Phase 2 path:");
  console.log(phase2Path);

  console.log("\nPhase 3 path:");
  console.log(phase3Path);

  const phase2 = readCsv(phase2Path, "Phase 2 dataset");
  const phase3 = readCsv(phase3Path, "Phase 3 dataset");

  validateHeader(phase2.header, "Phase 2 dataset");
  validateHeader(phase3.header, "Phase 3 dataset");

  validateRows(phase2.rows, "Phase 2 dataset");
  validateRows(phase3.rows, "Phase 3 dataset");

  const combinedRows = [...phase2.rows, ...phase3.rows];

  writeCsv(outputPath, combinedRows);

  console.log("\nCombined dataset created successfully.");
  console.log(`Output: ${outputPath}`);

  console.log("\nRow summary:");
  console.log(`Phase 2 rows: ${phase2.rows.length}`);
  console.log(`Phase 3 rows: ${phase3.rows.length}`);
  console.log(`Combined rows: ${combinedRows.length}`);

  console.log("\nFriction level distribution:");
  console.table(countByColumn(combinedRows, "friction_level"));

  console.log("\nSource dataset distribution:");
  console.table(countByColumn(combinedRows, "source_dataset"));

  console.log("\nFinal schema:");
  console.log(REQUIRED_COLUMNS.join(","));
}

main();
