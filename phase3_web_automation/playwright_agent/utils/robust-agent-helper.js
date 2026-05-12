const { expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const OUTPUT_ROOT = path.join(__dirname, "../outputs");
const SCREENSHOT_ROOT = path.join(OUTPUT_ROOT, "screenshots");
const LOG_ROOT = path.join(OUTPUT_ROOT, "logs");
const DATASET_ROOT = path.join(OUTPUT_ROOT, "datasets");
const RAW_DATASET_PATH = path.join(
  DATASET_ROOT,
  "agent_generated_ux_dataset.csv",
);
const CONFIG_PATH = path.join(__dirname, "../config/site-selectors.json");

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

const viewportMap = {
  mobile: { width: 390, height: 844 },
  tablet: { width: 768, height: 1024 },
  laptop: { width: 1280, height: 800 },
  desktop: { width: 1920, height: 1080 },
};

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, "utf8"));
}

function generateRunId(flowType, scenario, siteStyle, viewportType) {
  const randomPart = Math.random().toString(36).slice(2, 8);
  return `${siteStyle}_${flowType}_${scenario}_${viewportType}_${Date.now()}_${randomPart}`;
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

function createTracker({
  flowType,
  scenario,
  pageUrl,
  siteStyle,
  viewportType,
}) {
  const runId = generateRunId(flowType, scenario, siteStyle, viewportType);
  const screenshotDir = path.join(SCREENSHOT_ROOT, runId);

  ensureDir(screenshotDir);
  ensureDir(LOG_ROOT);
  ensureDir(DATASET_ROOT);

  return {
    runId,
    flowType,
    scenario,
    pageUrl,
    siteStyle,
    viewportType,
    screenshotDir,
    startTime: Date.now(),
    logs: [],
    stepNumber: 1,
    details: {
      selector_used: [],
      selector_attempts: [],
      popup_detected: false,
      popup_closed: false,
      captcha_detected: false,
      failure_reason: [],
      viewport_type: viewportType,
      site_style: siteStyle,
    },
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

function addLog(
  tracker,
  actionType,
  targetElement,
  actionStatus,
  message,
  extra = {},
) {
  tracker.logs.push({
    run_id: tracker.runId,
    step_number: tracker.stepNumber,
    action_type: actionType,
    target_element: targetElement,
    action_status: actionStatus,
    timestamp: new Date().toISOString(),
    message,
    page_url: tracker.pageUrl,
    site_style: tracker.siteStyle,
    viewport_type: tracker.viewportType,
    popup_detected: tracker.details.popup_detected,
    captcha_detected: tracker.details.captcha_detected,
    failure_reason: tracker.details.failure_reason,
    ...extra,
  });
  tracker.stepNumber += 1;
}

function recordFailure(tracker, reason, message) {
  if (!tracker.details.failure_reason.includes(reason)) {
    tracker.details.failure_reason.push(reason);
  }
  addLog(tracker, "failure_reason", reason, "recorded", message || reason);
}

async function takeScreenshot(page, tracker, name) {
  const screenshotPath = path.join(tracker.screenshotDir, `${name}.png`);
  await page.screenshot({ path: screenshotPath, fullPage: true });
  tracker.metrics.screenshot_count += 1;
  addLog(tracker, "screenshot", "page", "success", `Captured ${name}`, {
    screenshot_path: screenshotPath,
  });
  return screenshotPath;
}

async function extractErrorMessageClarity(page, tracker) {
  const candidates = [
    page.locator("#message"),
    page.locator(".validation-message").first(),
    page.locator("body"),
  ];

  for (const locator of candidates) {
    const clarityText = await locator
      .getAttribute("data-error-message-clarity")
      .catch(() => null);
    const clarity = Number(clarityText);

    if ([2, 1, 0, -1].includes(clarity)) {
      tracker.metrics.error_message_clarity = clarity;
      return clarity;
    }
  }

  tracker.metrics.error_message_clarity = -1;
  return -1;
}

function loadSelectorConfig() {
  if (!fs.existsSync(CONFIG_PATH)) {
    throw new Error(`Selector config not found: ${CONFIG_PATH}`);
  }

  return readJson(CONFIG_PATH);
}

function getSiteConfig(config, pageUrl) {
  const siteName = Object.keys(config).find((name) => {
    if (name === "default") return false;
    return (
      config[name].matchUrlIncludes &&
      pageUrl.includes(config[name].matchUrlIncludes)
    );
  });

  return {
    siteName: siteName || "default",
    defaultConfig: config.default || {},
    siteConfig: siteName ? config[siteName] : {},
  };
}

function getSelectorList(config, pageUrl, flowType, elementKey) {
  const { siteConfig, defaultConfig } = getSiteConfig(config, pageUrl);
  const siteSelectors = siteConfig[flowType]?.[elementKey] || [];
  const defaultSelectors = defaultConfig[flowType]?.[elementKey] || [];

  return {
    selectors: [...siteSelectors, ...defaultSelectors],
  };
}

async function findFirstVisible(page, tracker, selectors, label) {
  for (const selector of selectors) {
    tracker.details.selector_attempts.push({ label, selector });

    const locator = page.locator(selector).first();

    try {
      await locator.waitFor({ state: "visible", timeout: 650 });
      tracker.details.selector_used.push({ label, selector });
      addLog(
        tracker,
        "selector",
        selector,
        "success",
        `Selector found for ${label}`,
        {
          selector_used: selector,
        },
      );
      return { locator, selector };
    } catch (error) {
      addLog(
        tracker,
        "selector",
        selector,
        "failed",
        `Selector failed for ${label}`,
      );
    }
  }

  tracker.metrics.failed_clicks += 1;
  tracker.metrics.retry_count += 1;
  recordFailure(tracker, "element_not_found", `No selector found for ${label}`);
  throw new Error(`No visible selector found for ${label}`);
}

async function robustFill(page, tracker, config, elementKey, value, label) {
  const { selectors } = getSelectorList(
    config,
    tracker.pageUrl,
    tracker.flowType,
    elementKey,
  );
  const { locator, selector } = await findFirstVisible(
    page,
    tracker,
    selectors,
    label,
  );

  await locator.fill(value);
  tracker.metrics.keyboard_count += 1;

  addLog(tracker, "keyboard", selector, "success", label, {
    selector_used: selector,
  });
}

async function robustSelect(page, tracker, config, elementKey, value, label) {
  const { selectors } = getSelectorList(
    config,
    tracker.pageUrl,
    tracker.flowType,
    elementKey,
  );

  try {
    const { locator, selector } = await findFirstVisible(
      page,
      tracker,
      selectors,
      label,
    );
    await locator
      .selectOption({ label: value })
      .catch(async () => locator.selectOption(value));
    tracker.metrics.click_count += 1;

    addLog(tracker, "select", selector, "success", label, {
      selector_used: selector,
    });
  } catch (error) {
    tracker.metrics.retry_count += 1;
    recordFailure(
      tracker,
      "selector_failed",
      `Optional selector failed for ${label}`,
    );
  }
}

async function robustCheck(page, tracker, config, elementKey, label) {
  const { selectors } = getSelectorList(
    config,
    tracker.pageUrl,
    tracker.flowType,
    elementKey,
  );

  try {
    const { locator, selector } = await findFirstVisible(
      page,
      tracker,
      selectors,
      label,
    );
    await locator.check();
    tracker.metrics.click_count += 1;

    addLog(tracker, "click", selector, "success", label, {
      selector_used: selector,
    });
  } catch (error) {
    tracker.metrics.retry_count += 1;
    recordFailure(
      tracker,
      "selector_failed",
      `Optional checkbox failed for ${label}`,
    );
  }
}

async function robustClick(page, tracker, config, elementKey, label) {
  const { selectors } = getSelectorList(
    config,
    tracker.pageUrl,
    tracker.flowType,
    elementKey,
  );
  const feedbackStart = Date.now();
  const { locator, selector } = await findFirstVisible(
    page,
    tracker,
    selectors,
    label,
  );

  await locator.click({ timeout: 1200 });
  tracker.metrics.click_count += 1;

  addLog(tracker, "click", selector, "success", label, {
    selector_used: selector,
  });

  try {
    await expect(
      page.locator("#message, .validation-message").first(),
    ).toHaveAttribute("data-status", /success|error|loading/, {
      timeout: 2500,
    });
  } catch (error) {
    tracker.metrics.failed_clicks += 1;
    tracker.metrics.retry_count += 1;
    recordFailure(
      tracker,
      "button_no_response",
      `Click did not produce immediate response: ${label}`,
    );
  }

  try {
    await expect(
      page.locator("#message, .validation-message").first(),
    ).toHaveAttribute("data-status", "success", { timeout: 10000 });

    tracker.metrics.feedback_delay = Date.now() - feedbackStart;
    return true;
  } catch (error) {
    const hasError = await page
      .locator(
        "#message[data-status='error'], .validation-message[data-status='error']",
      )
      .first()
      .isVisible()
      .catch(() => false);

    if (hasError) {
      tracker.metrics.error_count += 1;
      tracker.metrics.retry_count += 1;
      await extractErrorMessageClarity(page, tracker);
      recordFailure(
        tracker,
        "form_validation_failed",
        `Validation appeared after ${label}`,
      );
    } else {
      tracker.metrics.error_count += 1;
      recordFailure(
        tracker,
        "timeout",
        `Success did not appear after ${label}`,
      );
    }

    return false;
  }
}

async function trackedScroll(page, tracker, y, label) {
  await page.mouse.wheel(0, y);
  tracker.metrics.scroll_count += 1;
  addLog(tracker, "scroll", "page", "success", label);
}

async function handlePopups(page, tracker) {
  const popupCloseSelectors = [
    "#acceptCookies",
    "button:has-text('Accept Cookies')",
    "button:has-text('Accept')",
    "#closeNewsletter",
    "button[aria-label*='Close' i]",
    "button:has-text('Close')",
    "#closeOverlay",
    "button:has-text('Dismiss overlay')",
  ];

  const popupContainers = [
    "#cookiePopup",
    "#newsletterModal",
    "#loadingOverlay",
    ".modal-backdrop",
    ".popup-banner",
    ".blocking-overlay",
  ];

  for (const container of popupContainers) {
    if (
      await page
        .locator(container)
        .first()
        .isVisible()
        .catch(() => false)
    ) {
      tracker.details.popup_detected = true;
      addLog(
        tracker,
        "popup",
        container,
        "detected",
        `Popup detected: ${container}`,
      );
      break;
    }
  }

  for (const selector of popupCloseSelectors) {
    const closeButton = page.locator(selector).first();

    if (await closeButton.isVisible().catch(() => false)) {
      await closeButton.click({ timeout: 900 });
      tracker.metrics.click_count += 1;
      tracker.details.popup_closed = true;

      addLog(
        tracker,
        "popup",
        selector,
        "closed",
        `Popup closed using ${selector}`,
        {
          selector_used: selector,
        },
      );

      await page.waitForTimeout(250);
      return;
    }
  }

  if (tracker.details.popup_detected && !tracker.details.popup_closed) {
    tracker.metrics.failed_clicks += 1;
    tracker.metrics.retry_count += 1;
    recordFailure(
      tracker,
      "popup_blocked",
      "Popup detected but no close button was found",
    );
  }
}

async function detectCaptchaOrSecurityBlock(page, tracker) {
  const securitySelectors = [
    "#captchaBox",
    "[data-security='captcha']",
    "iframe[src*='captcha' i]",
    "text=/captcha|security verification|robot/i",
  ];

  for (const selector of securitySelectors) {
    if (
      await page
        .locator(selector)
        .first()
        .isVisible()
        .catch(() => false)
    ) {
      tracker.details.captcha_detected = true;
      tracker.metrics.error_count += 1;
      recordFailure(
        tracker,
        "captcha_detected",
        `Security block detected by ${selector}. CAPTCHA is not bypassed.`,
      );
      return true;
    }
  }

  return false;
}

async function performRobustSignup(page, tracker, config) {
  await robustFill(
    page,
    tracker,
    config,
    "email",
    "phase35@example.com",
    "Filled signup email",
  );
  await robustFill(
    page,
    tracker,
    config,
    "password",
    "Password123!",
    "Filled signup password",
  );

  if (tracker.scenario !== "good") {
    await robustFill(
      page,
      tracker,
      config,
      "company",
      "GAgent Test Lab",
      "Filled company field",
    );
    await robustCheck(
      page,
      tracker,
      config,
      "terms",
      "Accepted terms if present",
    );
  }

  if (tracker.scenario === "bad") {
    await trackedScroll(
      page,
      tracker,
      950,
      "Scrolled to locate hidden signup action",
    );
  }

  let completed = await robustClick(
    page,
    tracker,
    config,
    "submit",
    "Clicked signup submit",
  );

  if (!completed && tracker.scenario !== "bad") {
    await robustCheck(
      page,
      tracker,
      config,
      "terms",
      "Retry: accepted terms after validation",
    );
    completed = await robustClick(
      page,
      tracker,
      config,
      "submit",
      "Retry signup submit",
    );
  }

  return completed;
}

async function performRobustLogin(page, tracker, config) {
  await robustFill(
    page,
    tracker,
    config,
    "user",
    "user123",
    "Filled login user field",
  );
  await robustFill(
    page,
    tracker,
    config,
    "password",
    tracker.scenario === "medium" ? "wrong" : "123456",
    "Filled login password/PIN",
  );

  if (tracker.scenario !== "good") {
    await robustFill(
      page,
      tracker,
      config,
      "securityAnswer",
      "testing",
      "Filled security answer if present",
    );
  }

  if (tracker.scenario === "bad") {
    await detectCaptchaOrSecurityBlock(page, tracker);
    await trackedScroll(
      page,
      tracker,
      850,
      "Scrolled around security-blocked login layout",
    );
  }

  let completed = await robustClick(
    page,
    tracker,
    config,
    "submit",
    "Clicked login submit",
  );

  if (!completed && tracker.scenario === "medium") {
    await robustFill(
      page,
      tracker,
      config,
      "password",
      "123456",
      "Retry: corrected password/PIN",
    );
    completed = await robustClick(
      page,
      tracker,
      config,
      "submit",
      "Retry login submit",
    );
  }

  return tracker.details.captcha_detected ? false : completed;
}

async function performRobustSearch(page, tracker, config) {
  await robustFill(
    page,
    tracker,
    config,
    "query",
    tracker.flowType === "search" ? "data analyst" : "query",
    "Filled search query",
  );

  if (tracker.scenario !== "good") {
    await robustSelect(
      page,
      tracker,
      config,
      "category",
      "Electronics",
      "Selected category if present",
    );
    await robustFill(
      page,
      tracker,
      config,
      "location",
      "Kuala Lumpur",
      "Filled location if present",
    );
  }

  if (tracker.scenario === "bad") {
    await trackedScroll(page, tracker, 900, "Scrolled to hidden search action");
  }

  let completed = await robustClick(
    page,
    tracker,
    config,
    "submit",
    "Clicked search submit",
  );

  if (!completed && tracker.scenario !== "good") {
    await robustSelect(
      page,
      tracker,
      config,
      "category",
      "Electronics",
      "Retry: selected category",
    );
    await robustFill(
      page,
      tracker,
      config,
      "location",
      "Kuala Lumpur",
      "Retry: filled location",
    );
    completed = await robustClick(
      page,
      tracker,
      config,
      "submit",
      "Retry search submit",
    );
  }

  return completed;
}

async function performRobustCta(page, tracker, config) {
  if (tracker.scenario === "bad") {
    await trackedScroll(
      page,
      tracker,
      1150,
      "Scrolled through dense dashboard to find CTA",
    );
  }

  const completed = await robustClick(
    page,
    tracker,
    config,
    "submit",
    "Clicked CTA action",
  );
  return completed;
}

async function performRobustFlow(page, tracker, config) {
  if (tracker.flowType === "signup")
    return performRobustSignup(page, tracker, config);
  if (tracker.flowType === "login")
    return performRobustLogin(page, tracker, config);
  if (tracker.flowType === "search")
    return performRobustSearch(page, tracker, config);
  if (tracker.flowType === "cta")
    return performRobustCta(page, tracker, config);

  recordFailure(
    tracker,
    "unsupported_layout",
    `Unsupported flow type: ${tracker.flowType}`,
  );
  throw new Error(`Unsupported flow type: ${tracker.flowType}`);
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
    friction_level: frictionLevelMap[tracker.scenario] || "High",
  };
}

function saveRunOutputs(tracker) {
  const logPath = path.join(LOG_ROOT, `${tracker.runId}.json`);

  const logPayload = {
    run_id: tracker.runId,
    summary: {
      site_style: tracker.siteStyle,
      viewport_type: tracker.viewportType,
      flow_type: tracker.flowType,
      page_url: tracker.pageUrl,
      friction_scenario: tracker.scenario,
      friction_level: frictionLevelMap[tracker.scenario] || "High",
      selector_used: tracker.details.selector_used,
      selector_attempts: tracker.details.selector_attempts,
      popup_detected: tracker.details.popup_detected,
      popup_closed: tracker.details.popup_closed,
      captcha_detected: tracker.details.captcha_detected,
      failure_reason: tracker.details.failure_reason,
      metrics: tracker.metrics,
    },
    steps: tracker.logs,
  };

  fs.writeFileSync(logPath, JSON.stringify(logPayload, null, 2));

  const row = buildDatasetRow(tracker);
  appendCSVRow(RAW_DATASET_PATH, RAW_COLUMNS, row);

  console.log("Robust dataset row generated:", row);
  return row;
}

async function runRobustScenario(page, scenarioData) {
  const config = loadSelectorConfig();
  const viewportType = scenarioData.viewportType || "laptop";
  const viewport = viewportMap[viewportType] || viewportMap.laptop;

  await page.setViewportSize(viewport);

  const tracker = createTracker({
    flowType: scenarioData.flowType,
    scenario: scenarioData.scenario,
    pageUrl: scenarioData.pageUrl,
    siteStyle: scenarioData.siteStyle || "unknown",
    viewportType,
  });

  try {
    await page.goto(scenarioData.pageUrl, { waitUntil: "domcontentloaded" });

    addLog(
      tracker,
      "navigate",
      scenarioData.pageUrl,
      "success",
      `Opened ${scenarioData.pageUrl}`,
    );

    await extractErrorMessageClarity(page, tracker);
    await handlePopups(page, tracker);
    await detectCaptchaOrSecurityBlock(page, tracker);
    await takeScreenshot(page, tracker, "01_page_load_after_popup_handling");

    const completed = await performRobustFlow(page, tracker, config);
    tracker.metrics.task_completed = completed ? 1 : 0;

    if (!completed && tracker.details.failure_reason.length === 0) {
      recordFailure(
        tracker,
        "unsupported_layout",
        "Task was not completed and no specific failure reason was recorded",
      );
    }

    await extractErrorMessageClarity(page, tracker);
    await takeScreenshot(
      page,
      tracker,
      completed ? "99_after_success" : "99_after_incomplete",
    );
  } catch (error) {
    tracker.metrics.error_count += 1;
    tracker.metrics.task_completed = 0;

    recordFailure(tracker, "timeout", error.message.split("\n")[0]);
    await takeScreenshot(page, tracker, "error_state").catch(() => "");
    addLog(tracker, "error", "test", "failed", error.message);
  }

  return saveRunOutputs(tracker);
}

module.exports = {
  RAW_COLUMNS,
  RAW_DATASET_PATH,
  viewportMap,
  runRobustScenario,
};
