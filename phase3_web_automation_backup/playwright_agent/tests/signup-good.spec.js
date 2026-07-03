const { test } = require("@playwright/test");
const { runScenario } = require("../utils/metrics-helper");

test("GAgent signup good test", async ({ page }) => {
  await runScenario(page, {
    flowType: "signup",
    scenario: "good",
    pageUrl: "http://localhost:3000/signup-good",
  });
});
