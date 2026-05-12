const { test } = require("@playwright/test");
const { runScenario } = require("../utils/metrics-helper");

test("GAgent signup medium test", async ({ page }) => {
  await runScenario(page, {
    flowType: "signup",
    scenario: "medium",
    pageUrl: "http://localhost:3000/signup-medium",
  });
});
