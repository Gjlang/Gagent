const { expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const OUTPUT_ROOT = path.join(__dirname, "../outputs");
const SCREENSHOT_ROOT = path.join(OUTPUT_ROOT, "screenshots");
const LOG_ROOT = path.join(OUTPUT_ROOT, "logs");
const DATASET_ROOT = path.join(OUTPUT_ROOT, "datasets");
const RAW_DATASET_PATH = path.join(DATASET_ROOT, "agent_generated_ux_dataset.csv");

const SOURCE_DATASET = "playwright_dummy_website";

const RAW_COLUMNS = [
  "run_id",
  "source_dataset",
  "flow_type",
  "page_url",
  "friction_scenario",
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

const frictionLevelMap = {
  good: "Low",
  medium: "Medium",
  bad: "High",
};

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function generateRunId(flowType, scenario) {
  const randomPart = Math.random().toString(36).slice(2, 8);
  return `${flowType}_${scenario}_${Date.now()}_${randomPart}`;
}

function csvEscape(value) {
  if (value === null || value === undefined) return "";
  const text = String(value);
  if (text.includes(",") || text.includes("\n") || text.includes('"')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

function appendCSVRow(filePath, columns, row) {
  ensureDir(path.dirname(filePath));

  if (!fs.existsSync(filePath)) {
    fs.writeFileSync(filePath, `${columns.join(",")}\n`);
  }

  const line = columns.map((column) => csvEscape(row[column])).join(",");
  fs.appendFileSync(filePath, `${line}\n`);
}

function createTracker({ flowType, scenario, pageUrl }) {
  const runId = generateRunId(flowType, scenario);
  const screenshotDir = path.join(SCREENSHOT_ROOT, runId);

  ensureDir(screenshotDir);
  ensureDir(LOG_ROOT);
  ensureDir(DATASET_ROOT);

  return {
    runId,
    flowType,
    scenario,
    pageUrl,
    screenshotDir,
    startTime: Date.now(),
    logs: [],
    stepNumber: 1,
    metrics: {
      click_count: 0,
      scroll_count: 0,
      keyboard_count: 0,
      retry_count: 0,
      error_count: 0,
      failed_clicks: 0,
      feedback_delay: 0,
      task_completed: 0,
      screenshot_count: 0,
      error_message_clarity: -1,
    },
  };
}

function addLog(tracker, actionType, targetElement, actionStatus, message, screenshotPath = "") {
  tracker.logs.push({
    run_id: tracker.runId,
    step_number: tracker.stepNumber,
    action_type: actionType,
    target_element: targetElement,
    action_status: actionStatus,
    timestamp: new Date().toISOString(),
    message,
    screenshot_path: screenshotPath,
  });
  tracker.stepNumber += 1;
}

async function takeScreenshot(page, tracker, name) {
  const screenshotPath = path.join(tracker.screenshotDir, `${name}.png`);
  await page.screenshot({ path: screenshotPath, fullPage: true });
  tracker.metrics.screenshot_count += 1;
  addLog(tracker, "screenshot", "page", "success", `Captured ${name}`, screenshotPath);
  return screenshotPath;
}

async function extractErrorMessageClarity(page, tracker) {
  const clarityText = await page
    .locator("body")
    .getAttribute("data-error-message-clarity")
    .catch(() => null);

  const clarity = Number(clarityText);
  if ([2, 1, 0, -1].includes(clarity)) {
    tracker.metrics.error_message_clarity = clarity;
    return clarity;
  }

  tracker.metrics.error_message_clarity = -1;
  return -1;
}

async function trackedClick(page, tracker, selector, label) {
  await page.click(selector);
  tracker.metrics.click_count += 1;
  addLog(tracker, "click", selector, "success", label);
}

async function trackedRoleClick(page, tracker, roleOptions, label, timeout = 1200) {
  await page.getByRole("button", roleOptions).click({ timeout });
  tracker.metrics.click_count += 1;
  addLog(tracker, "click", `role=button ${JSON.stringify(roleOptions)}`, "success", label);
}

async function trackedFailedClick(page, tracker, selector, label, mode = "selector") {
  try {
    if (mode === "role-buy-now") {
      await page.getByRole("button", { name: "Buy Now" }).click({ timeout: 900 });
    } else {
      await page.click(selector, { timeout: 900 });
      tracker.metrics.click_count += 1;
    }

    tracker.metrics.failed_clicks += 1;
    tracker.metrics.retry_count += 1;
    addLog(tracker, "click", selector, "failed", label);
  } catch (error) {
    tracker.metrics.failed_clicks += 1;
    tracker.metrics.retry_count += 1;
    addLog(tracker, "click", selector, "failed", `${label}. ${error.message.split("\n")[0]}`);
  }
}

async function trackedFill(page, tracker, selector, value, label) {
  await page.fill(selector, value);
  tracker.metrics.keyboard_count += 1;
  addLog(tracker, "keyboard", selector, "success", label);
}

async function trackedCheck(page, tracker, selector, label) {
  await page.check(selector);
  tracker.metrics.click_count += 1;
  addLog(tracker, "click", selector, "success", label);
}

async function trackedScroll(page, tracker, y, label) {
  await page.mouse.wheel(0, y);
  tracker.metrics.scroll_count += 1;
  addLog(tracker, "scroll", "page", "success", label);
}

async function waitForValidationError(page, tracker, expectedText, screenshotName) {
  await expect(page.locator("#message")).toContainText(expectedText, { timeout: 3000 });
  tracker.metrics.error_count += 1;
  tracker.metrics.retry_count += 1;
  await extractErrorMessageClarity(page, tracker);
  addLog(tracker, "validation", "#message", "failed", `Validation appeared: ${expectedText}`);
  await takeScreenshot(page, tracker, screenshotName);
}

async function clickAndWaitForSuccess(page, tracker, selector, successText, timeout, label) {
  const feedbackStart = Date.now();
  await trackedClick(page, tracker, selector, label);
  await expect(page.locator("#message")).toContainText(successText, { timeout });
  tracker.metrics.feedback_delay = Date.now() - feedbackStart;
  addLog(tracker, "assert", "#message", "success", `Success message appeared: ${successText}`);
}

async function roleClickAndWaitForSuccess(page, tracker, roleOptions, successText, timeout, label) {
  const feedbackStart = Date.now();
  await trackedRoleClick(page, tracker, roleOptions, label);
  await expect(page.locator("#message")).toContainText(successText, { timeout });
  tracker.metrics.feedback_delay = Date.now() - feedbackStart;
  addLog(tracker, "assert", "#message", "success", `Success message appeared: ${successText}`);
}

async function performSignup(page, tracker, scenario) {
  if (scenario === "good") {
    await trackedFill(page, tracker, "#name", "Alya Tester", "Filled full name");
    await trackedFill(page, tracker, "#email", "alya@example.com", "Filled email");
    await trackedFill(page, tracker, "#password", "Password123!", "Filled password");
    await takeScreenshot(page, tracker, "02_before_submit");
    await clickAndWaitForSuccess(page, tracker, "#submitBtn", "Signup completed successfully", 5000, "Submitted clear signup form");
    return;
  }

  if (scenario === "medium") {
    await trackedFill(page, tracker, "#name", "Medium Tester", "Filled full name");
    await trackedFill(page, tracker, "#email", "medium@example.com", "Filled email");
    await trackedClick(page, tracker, "#submitBtn", "Submitted incomplete signup form");
    await waitForValidationError(page, tracker, "Some registration details are incomplete", "02_partial_validation_error");
    await trackedFill(page, tracker, "#password", "Password123!", "Filled password after validation");
    await trackedFill(page, tracker, "#referral", "REF-MED", "Filled referral code");
    await trackedCheck(page, tracker, "#terms", "Accepted terms after retry");
    await takeScreenshot(page, tracker, "03_before_retry_submit");
    await clickAndWaitForSuccess(page, tracker, "#submitBtn", "Signup completed successfully", 7000, "Retried signup form successfully");
    return;
  }

  await trackedFailedClick(page, tracker, "#fakeSignupBtn", "Fake signup button did not submit the form");
  await takeScreenshot(page, tracker, "02_fake_button_clicked");
  await trackedClick(page, tracker, "#submitBtn", "Submitted empty vague signup form");
  await waitForValidationError(page, tracker, "Problem. Fix it.", "03_vague_validation_error");
  await trackedFill(page, tracker, "#name", "Bad Tester", "Filled vague name field");
  await trackedFill(page, tracker, "#email", "bad@example.com", "Filled vague email field");
  await trackedFill(page, tracker, "#password", "Password123!", "Filled vague password field");
  await trackedFill(page, tracker, "#referral", "REF-BAD", "Filled vague referral field");
  await trackedCheck(page, tracker, "#terms", "Checked unclear required option");
  await trackedScroll(page, tracker, 900, "Scrolled to hidden signup submit button");
  await takeScreenshot(page, tracker, "04_after_scroll_to_submit");
  await clickAndWaitForSuccess(page, tracker, "#submitBtn", "Signup completed successfully", 9000, "Submitted hidden vague signup form successfully");
}

async function performLogin(page, tracker, scenario) {
  if (scenario === "good") {
    await trackedFill(page, tracker, "#email", "user@example.com", "Filled login email");
    await trackedFill(page, tracker, "#password", "Password123!", "Filled login password");
    await takeScreenshot(page, tracker, "02_before_login");
    await clickAndWaitForSuccess(page, tracker, "#loginBtn", "Login successful", 5000, "Submitted clear login form");
    return;
  }

  if (scenario === "medium") {
    await trackedFill(page, tracker, "#email", "user@example.com", "Filled login email");
    await trackedFill(page, tracker, "#password", "wrong-password", "Filled wrong password");
    await trackedClick(page, tracker, "#loginBtn", "Submitted wrong login details");
    await waitForValidationError(page, tracker, "The login details do not match", "02_partial_login_error");
    await trackedFill(page, tracker, "#password", "Password123!", "Corrected password after retry");
    await takeScreenshot(page, tracker, "03_before_login_retry");
    await clickAndWaitForSuccess(page, tracker, "#loginBtn", "Login successful", 7000, "Retried login successfully");
    return;
  }

  await trackedFailedClick(page, tracker, "#fakeLoginBtn", "Fake login button did not submit credentials");
  await takeScreenshot(page, tracker, "02_fake_login_button");
  await trackedFill(page, tracker, "#email", "user@example.com", "Filled vague user identifier");
  await trackedFill(page, tracker, "#password", "wrong-password", "Filled wrong access key");
  await trackedScroll(page, tracker, 500, "Scrolled to hidden vague login button");
  await trackedClick(page, tracker, "#loginBtn", "Submitted vague wrong login details");
  await waitForValidationError(page, tracker, "No. Try again.", "03_vague_login_error");
  await trackedFill(page, tracker, "#password", "Password123!", "Corrected access key after vague error");
  await takeScreenshot(page, tracker, "04_before_final_login");
  await clickAndWaitForSuccess(page, tracker, "#loginBtn", "Login successful", 9000, "Submitted vague login form successfully");
}

async function performSearch(page, tracker, scenario) {
  if (scenario === "good") {
    await trackedFill(page, tracker, "#searchInput", "laptop", "Typed clear search keyword");
    await takeScreenshot(page, tracker, "02_before_search");
    await clickAndWaitForSuccess(page, tracker, "#searchBtn", "Search completed", 5000, "Clicked clear search button");
    return;
  }

  if (scenario === "medium") {
    await trackedFailedClick(page, tracker, "#clearSearchFake", "Disabled clear search control could not be used");
    await takeScreenshot(page, tracker, "02_disabled_control");
    await trackedFill(page, tracker, "#searchInput", "phone", "Typed search keyword after retry");
    await clickAndWaitForSuccess(page, tracker, "#searchBtn", "Search completed", 7000, "Clicked less direct Find button");
    return;
  }

  await trackedFailedClick(page, tracker, "#fakeSearchBtn", "Fake search button did not start search");
  await takeScreenshot(page, tracker, "02_fake_search_button");
  await trackedScroll(page, tracker, 900, "Scrolled to hidden vague search button");
  await trackedClick(page, tracker, "#searchBtn", "Clicked hidden search button with empty query");
  await waitForValidationError(page, tracker, "Missing.", "03_vague_search_error");
  await trackedFill(page, tracker, "#searchInput", "camera", "Typed query after vague validation");
  await takeScreenshot(page, tracker, "04_before_final_search");
  await clickAndWaitForSuccess(page, tracker, "#searchBtn", "Search completed", 9000, "Retried hidden search successfully");
}

async function performLanding(page, tracker, scenario) {
  if (scenario === "good") {
    await takeScreenshot(page, tracker, "02_before_cta");
    await clickAndWaitForSuccess(page, tracker, "#mainCta", "Landing task completed", 5000, "Clicked visible landing CTA");
    return;
  }

  if (scenario === "medium") {
    await trackedFill(page, tracker, "#interestInput", "compare plans", "Typed optional goal before starting");
    await trackedScroll(page, tracker, 350, "Scrolled through extra landing content");
    await takeScreenshot(page, tracker, "02_after_scanning_content");
    await clickAndWaitForSuccess(page, tracker, "#mainCta", "Landing task completed", 7000, "Clicked medium landing CTA");
    return;
  }

  await trackedFailedClick(page, tracker, "#fakeLandingCta", "Fake top landing CTA did not complete the task");
  await takeScreenshot(page, tracker, "02_fake_landing_cta");
  await trackedScroll(page, tracker, 900, "Scrolled to hidden real landing CTA");
  await takeScreenshot(page, tracker, "03_after_scroll_to_real_cta");
  await clickAndWaitForSuccess(page, tracker, "#mainCta", "Landing task completed", 9000, "Clicked hidden real landing CTA");
}

async function performCta(page, tracker, scenario) {
  if (scenario === "good") {
    await takeScreenshot(page, tracker, "02_before_cta");
    await roleClickAndWaitForSuccess(page, tracker, { name: "Buy Now" }, "CTA task completed", 5000, "Clicked clear Buy Now CTA");
    return;
  }

  if (scenario === "medium") {
    await trackedFailedClick(page, tracker, "button[name='Buy Now']", "Clear Buy Now CTA was disabled or unavailable", "role-buy-now");
    await takeScreenshot(page, tracker, "02_failed_buy_now");
    await clickAndWaitForSuccess(page, tracker, "#ctaBtn", "CTA task completed", 7000, "Clicked indirect Proceed CTA");
    return;
  }

  await trackedFailedClick(page, tracker, "#fakeCtaBtn", "Fake visible CTA did not complete the task");
  await takeScreenshot(page, tracker, "02_fake_cta_clicked");
  await trackedScroll(page, tracker, 900, "Scrolled to hidden real CTA");
  await takeScreenshot(page, tracker, "03_after_scroll_to_cta");
  await clickAndWaitForSuccess(page, tracker, "#ctaBtn", "CTA task completed", 9000, "Clicked hidden vague CTA");
}

async function performFlow(page, tracker, scenarioData) {
  const flowHandlers = {
    signup: performSignup,
    login: performLogin,
    search: performSearch,
    landing: performLanding,
    cta: performCta,
  };

  const handler = flowHandlers[scenarioData.flowType];
  if (!handler) {
    throw new Error(`Unsupported flow type: ${scenarioData.flowType}`);
  }

  await handler(page, tracker, scenarioData.scenario);
}

function buildDatasetRow(tracker) {
  return {
    run_id: tracker.runId,
    source_dataset: SOURCE_DATASET,
    flow_type: tracker.flowType,
    page_url: tracker.pageUrl,
    friction_scenario: tracker.scenario,
    completion_time: Date.now() - tracker.startTime,
    click_count: tracker.metrics.click_count,
    scroll_count: tracker.metrics.scroll_count,
    keyboard_count: tracker.metrics.keyboard_count,
    retry_count: tracker.metrics.retry_count,
    error_count: tracker.metrics.error_count,
    failed_clicks: tracker.metrics.failed_clicks,
    feedback_delay: tracker.metrics.feedback_delay,
    task_completed: tracker.metrics.task_completed,
    screenshot_count: tracker.metrics.screenshot_count,
    error_message_clarity: tracker.metrics.error_message_clarity,
    friction_level: frictionLevelMap[tracker.scenario] || "Unknown",
  };
}

function saveRunOutputs(tracker) {
  const logPath = path.join(LOG_ROOT, `${tracker.runId}.json`);
  fs.writeFileSync(logPath, JSON.stringify(tracker.logs, null, 2));

  const row = buildDatasetRow(tracker);
  appendCSVRow(RAW_DATASET_PATH, RAW_COLUMNS, row);

  console.log("Dataset row generated:", row);
  return row;
}

async function runScenario(page, scenarioData) {
  const scenario = scenarioData.scenario;
  const flowType = scenarioData.flowType;
  const pageUrl = scenarioData.pageUrl || `http://localhost:3000/${flowType}-${scenario}`;
  const tracker = createTracker({ flowType, scenario, pageUrl });

  try {
    await page.goto(pageUrl);
    addLog(tracker, "navigate", pageUrl, "success", `Opened ${pageUrl}`);
    await extractErrorMessageClarity(page, tracker);
    await takeScreenshot(page, tracker, "01_page_load");

    await performFlow(page, tracker, { ...scenarioData, pageUrl });

    tracker.metrics.task_completed = 1;
    await takeScreenshot(page, tracker, "99_after_success");
  } catch (error) {
    tracker.metrics.error_count += 1;
    tracker.metrics.task_completed = 0;
    await takeScreenshot(page, tracker, "error_state").catch(() => "");
    addLog(tracker, "error", "test", "failed", error.message);
  }

  return saveRunOutputs(tracker);
}

module.exports = {
  RAW_COLUMNS,
  RAW_DATASET_PATH,
  frictionLevelMap,
  generateRunId,
  runScenario,
};
