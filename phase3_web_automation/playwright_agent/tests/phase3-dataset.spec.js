const fs = require("fs");
const path = require("path");
const { test } = require("@playwright/test");
const { initializeOutput, runPhase3Scenario } = require("../utils/phase3-metrics-collector");

const SCENARIO_PATH = path.join(__dirname, "../config/phase3-scenarios.json");
const scenarios = JSON.parse(fs.readFileSync(SCENARIO_PATH, "utf8"));

const mode = process.env.GAGENT_RUN_MODE || "pilot";
const repeats = Number(process.env.GAGENT_REPEATS || 1);
const viewportTypes = (process.env.GAGENT_VIEWPORTS || "desktop,tablet,mobile")
  .split(",")
  .map((item) => item.trim())
  .filter(Boolean);
const networkConditions = (process.env.GAGENT_NETWORKS || "normal")
  .split(",")
  .map((item) => item.trim())
  .filter(Boolean);

test.describe.configure({ mode: "serial" });

test.beforeAll(() => {
  initializeOutput(mode);
});

for (const scenario of scenarios) {
  for (const viewportType of viewportTypes) {
    for (const networkCondition of networkConditions) {
      for (let repeatIndex = 1; repeatIndex <= repeats; repeatIndex += 1) {
        test(`${mode} ${scenario.route} ${viewportType} ${networkCondition} run ${repeatIndex}`, async ({ page }) => {
          await runPhase3Scenario(page, scenario, {
            mode,
            viewportType,
            networkCondition,
            repeatIndex
          });
        });
      }
    }
  }
}
