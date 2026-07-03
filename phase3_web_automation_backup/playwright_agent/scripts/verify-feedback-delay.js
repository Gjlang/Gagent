const fs = require("fs");
const {
  FINAL_DATASET_PATH,
  readCSV,
  getNumber,
  groupBy,
  average,
  printProblems,
} = require("./verify-utils");

function main() {
  const problems = [];

  if (!fs.existsSync(FINAL_DATASET_PATH)) {
    problems.push({ problem: "Final CSV does not exist", expected_path: FINAL_DATASET_PATH });
    printProblems(problems, "", "Feedback delay verification failed.");
  }

  const rows = readCSV(FINAL_DATASET_PATH);
  const levels = groupBy(rows, "friction_level");

  const averages = {
    Low: average((levels.Low || []).map((row) => getNumber(row, "feedback_delay"))),
    Medium: average((levels.Medium || []).map((row) => getNumber(row, "feedback_delay"))),
    High: average((levels.High || []).map((row) => getNumber(row, "feedback_delay"))),
  };

  rows.forEach((row, index) => {
    const delay = getNumber(row, "feedback_delay");
    if (delay < 0) {
      problems.push({ row: index + 1, problem: "feedback_delay cannot be negative", value: row.feedback_delay });
    }
  });

  if (averages.Low > 0 && averages.Medium > 0 && averages.High > 0) {
    if (!(averages.Low < averages.Medium && averages.Medium < averages.High)) {
      problems.push({
        problem: "Average feedback_delay should generally follow Low < Medium < High",
        averages,
      });
    }
  } else {
    problems.push({ problem: "Could not calculate all class feedback_delay averages", averages });
  }

  printProblems(problems, "Feedback delay verification passed.", "Feedback delay verification failed.");

  console.log("Average feedback_delay by class:", averages);
}

main();
