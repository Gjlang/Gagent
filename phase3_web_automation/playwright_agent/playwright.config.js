const { defineConfig } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "./tests",
  fullyParallel: false,
  retries: 0,
  workers: 1,
  reporter: [["list"], ["html", { open: "never" }]],
  timeout: 90000,
  expect: { timeout: 8000 },
  use: {
    baseURL: process.env.GAGENT_BASE_URL || "http://localhost:3000",
    actionTimeout: 10000,
    navigationTimeout: 20000,
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
  },
});
