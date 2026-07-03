const { test } = require("@playwright/test");
const { runScenario } = require("../utils/metrics-helper");

const scenarios = ["good", "medium", "bad"];

for (const scenario of scenarios) {
  test(`GAgent landing ${scenario} test`, async ({ page }) => {
    await runScenario(page, {
      flowType: "landing",
      scenario,
      pageUrl: `http://localhost:3000/landing-${scenario}`,
    });
  });
}
