const fs = require("fs");
const {
  FINAL_DATASET_PATH,
  readCSV,
  groupBy,
  printProblems,
} = require("./verify-utils");

const MIN_EXPECTED_ROWS = Number(process.env.MIN_ROWS || 300);
const MIN_CLASS_RATIO = 0.15;

function main() {
  const problems = [];

  if (!fs.existsSync(FINAL_DATASET_PATH)) {
    problems.push({ problem: "Final CSV does not exist", expected_path: FINAL_DATASET_PATH });
    printProblems(problems, "", "Dataset row verification failed.");
  }

  const rows = readCSV(FINAL_DATASET_PATH);

  if (rows.length < MIN_EXPECTED_ROWS) {
    problems.push({
      problem: "Final CSV row count is below target",
      expected_minimum: MIN_EXPECTED_ROWS,
      actual: rows.length,
    });
  }

  const levels = groupBy(rows, "friction_level");
  ["Low", "Medium", "High"].forEach((level) => {
    const count = levels[level]?.length || 0;
    if (count === 0) {
      problems.push({ problem: "Missing class", friction_level: level });
    }

    if (rows.length >= MIN_EXPECTED_ROWS && count / rows.length < MIN_CLASS_RATIO) {
      problems.push({
        problem: "Class balance is too low",
        friction_level: level,
        count,
        ratio: count / rows.length,
        expected_minimum_ratio: MIN_CLASS_RATIO,
      });
    }
  });

  const flowCounts = groupBy(rows, "flow_type");
  ["landing", "signup", "login", "search", "cta"].forEach((flow) => {
    if (!flowCounts[flow] || flowCounts[flow].length === 0) {
      problems.push({ problem: "Missing flow_type", flow_type: flow });
    }
  });

  printProblems(problems, "Dataset row verification passed.", "Dataset row verification failed.");

  console.log("Total rows:", rows.length);
  console.log("Class counts:", Object.fromEntries(Object.entries(levels).map(([key, value]) => [key, value.length])));
  console.log("Flow counts:", Object.fromEntries(Object.entries(flowCounts).map(([key, value]) => [key, value.length])));
}

main();
