const { test } = require("@playwright/test");
const { runScenario } = require("../utils/metrics-helper");

test("GAgent signup bad test", async ({ page }) => {
  await runScenario(page, {
    flowType: "signup",
    scenario: "bad",
    pageUrl: "http://localhost:3000/signup-bad",
  });
});
