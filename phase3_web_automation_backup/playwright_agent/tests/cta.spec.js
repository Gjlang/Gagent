const { test } = require("@playwright/test");
const { runScenario } = require("../utils/metrics-helper");

const scenarios = ["good", "medium", "bad"];

for (const scenario of scenarios) {
  test(`GAgent CTA ${scenario} test`, async ({ page }) => {
    await runScenario(page, {
      flowType: "cta",
      scenario,
      pageUrl: `http://localhost:3000/cta-${scenario}`,
    });
  });
}
