const fs = require("fs");
const path = require("path");
const { labelRow } = require("./phase3-labeler");

const OUTPUT_ROOT = path.join(__dirname, "../outputs");
const PILOT_ROOT = path.join(OUTPUT_ROOT, "pilot");
const FINAL_ROOT = path.join(OUTPUT_ROOT, "final");
const LOG_ROOT = path.join(OUTPUT_ROOT, "logs_phase3");

const VIEWPORTS = {
  desktop: { width: 1366, height: 768 },
  tablet: { width: 768, height: 1024 },
  mobile: { width: 390, height: 844 },
};

const NETWORK_PROFILES = {
  fast: { extraDelayMs: 0 },
  normal: { extraDelayMs: 150 },
  slow: { extraDelayMs: 600 },
};

const DATASET_COLUMNS = [
  "flow_type",
  "scenario_type",
  "viewport_type",
  "network_condition",
  "task_completed",
  "task_failed",
  "completion_time",
  "click_count",
  "scroll_count",
  "keyboard_count",
  "retry_count",
  "error_count",
  "failed_clicks",
  "unnecessary_clicks",
  "path_deviation_score",
  "page_load_time_ms",
  "dom_content_loaded_ms",
  "time_to_first_byte_ms",
  "feedback_delay_ms",
  "interaction_to_next_paint_ms",
  "cumulative_layout_shift",
  "error_message_present",
  "error_message_clarity",
  "popup_detected",
  "cookie_banner_detected",
  "overlay_blocks_cta",
  "friction_score",
  "friction_level",
];

const AUDIT_COLUMNS = [
  ...DATASET_COLUMNS,
  "expected_friction_level",
  "label_mismatch",
  "route",
  "seed",
  "run_id",
];

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function csvEscape(value) {
  if (value === null || value === undefined) return "";
  const text = String(value);
  if (text.includes(",") || text.includes("\n") || text.includes('"')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}

function appendCsv(filePath, columns, row) {
  ensureDir(path.dirname(filePath));
  if (!fs.existsSync(filePath))
    fs.writeFileSync(filePath, `${columns.join(",")}\n`);
  fs.appendFileSync(
    filePath,
    `${columns.map((column) => csvEscape(row[column])).join(",")}\n`,
  );
}

function outputPaths(mode) {
  const root = mode === "final" ? FINAL_ROOT : PILOT_ROOT;
  return {
    root,
    dataset: path.join(
      root,
      mode === "final"
        ? "gagent_full_ux_friction_dataset.csv"
        : "gagent_phase3_pilot_dataset.csv",
    ),
    auditRows: path.join(
      root,
      mode === "final"
        ? "gagent_full_ux_friction_dataset_audit_rows.csv"
        : "gagent_phase3_pilot_audit_rows.csv",
    ),
  };
}

let COMPLETED_RUN_KEYS = new Set();

function parseSimpleCsvLine(line) {
  const result = [];
  let current = "";
  let insideQuotes = false;

  for (let index = 0; index < line.length; index += 1) {
    const char = line[index];
    const next = line[index + 1];

    if (char === '"' && insideQuotes && next === '"') {
      current += '"';
      index += 1;
    } else if (char === '"') {
      insideQuotes = !insideQuotes;
    } else if (char === "," && !insideQuotes) {
      result.push(current);
      current = "";
    } else {
      current += char;
    }
  }

  result.push(current);
  return result;
}

function completedKeyFromAuditRow(row) {
  const route = row.route;
  const viewportType = row.viewport_type;
  const networkCondition = row.network_condition;
  const runId = row.run_id || "";

  const parts = runId.split("_");
  const repeatIndex = parts.length >= 4 ? parts[parts.length - 3] : "";

  if (!route || !viewportType || !networkCondition || !repeatIndex) {
    return null;
  }

  return `${route}|${viewportType}|${networkCondition}|${repeatIndex}`;
}

function loadCompletedRunKeys(auditFilePath) {
  const completed = new Set();

  if (!fs.existsSync(auditFilePath)) {
    return completed;
  }

  const lines = fs
    .readFileSync(auditFilePath, "utf8")
    .split(/\r?\n/)
    .filter(Boolean);

  if (lines.length <= 1) {
    return completed;
  }

  const headers = parseSimpleCsvLine(lines[0]);

  for (const line of lines.slice(1)) {
    const values = parseSimpleCsvLine(line);
    const row = {};

    headers.forEach((header, index) => {
      row[header] = values[index];
    });

    const key = completedKeyFromAuditRow(row);
    if (key) completed.add(key);
  }

  return completed;
}

function initializeOutput(mode) {
  const paths = outputPaths(mode);
  ensureDir(paths.root);
  ensureDir(LOG_ROOT);

  const resumeMode =
    process.env.GAGENT_RESUME === "1" || process.env.GAGENT_APPEND === "1";

  if (resumeMode) {
    COMPLETED_RUN_KEYS = loadCompletedRunKeys(paths.auditRows);
    console.log(
      `Resume mode active. Existing completed rows found: ${COMPLETED_RUN_KEYS.size}`,
    );
    return;
  }

  [paths.dataset, paths.auditRows].forEach((filePath) => {
    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
  });

  COMPLETED_RUN_KEYS = new Set();
}

function createRunId(scenario, viewportType, networkCondition, repeatIndex) {
  const cleanRoute = scenario.route
    .replace(/^\//, "")
    .replace(/[^a-z0-9]+/gi, "_");
  return `${cleanRoute}_${viewportType}_${networkCondition}_${repeatIndex}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}

function round(value, digits = 0) {
  const factor = 10 ** digits;
  return Math.round((Number(value) || 0) * factor) / factor;
}

async function setupBrowserMetricListeners(page) {
  await page.addInitScript(() => {
    window.__gagentBrowserMetrics = {
      click_count: 0,
      scroll_count: 0,
      keyboard_count: 0,
      cumulative_layout_shift: 0,
      max_event_duration: 0,
      popup_detected: 0,
      cookie_banner_detected: 0,
      overlay_detected: 0,
    };

    function isVisibleElement(element) {
      if (!element) return false;
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        style.opacity !== "0" &&
        box.width > 0 &&
        box.height > 0
      );
    }

    function detectUxSignals() {
      const popupElement = document.querySelector(
        '[data-popup="true"], #modalOverlay, .modal-backdrop, .modal-card',
      );

      const cookieElement = document.querySelector(
        '[data-cookie-banner="true"], #cookieBanner, .cookie-banner',
      );

      const overlayElement = document.querySelector(
        '[data-overlay="true"], #blockingOverlay, #modalOverlay, .blocking-overlay, .modal-backdrop',
      );

      if (isVisibleElement(popupElement)) {
        window.__gagentBrowserMetrics.popup_detected = 1;
      }

      if (isVisibleElement(cookieElement)) {
        window.__gagentBrowserMetrics.cookie_banner_detected = 1;
      }

      if (isVisibleElement(overlayElement)) {
        window.__gagentBrowserMetrics.overlay_detected = 1;
      }
    }

    function startSignalObserver() {
      detectUxSignals();

      const observerTarget = document.documentElement || document.body;
      if (observerTarget) {
        const observer = new MutationObserver(() => {
          detectUxSignals();
        });

        observer.observe(observerTarget, {
          childList: true,
          subtree: true,
          attributes: true,
          attributeFilter: [
            "class",
            "style",
            "data-popup",
            "data-cookie-banner",
            "data-overlay",
          ],
        });
      }

      window.setInterval(detectUxSignals, 150);
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", startSignalObserver);
    } else {
      startSignalObserver();
    }

    document.addEventListener(
      "click",
      () => {
        window.__gagentBrowserMetrics.click_count += 1;
        detectUxSignals();
      },
      true,
    );

    document.addEventListener(
      "keydown",
      () => {
        window.__gagentBrowserMetrics.keyboard_count += 1;
      },
      true,
    );

    let scrollTimer = null;
    window.addEventListener(
      "scroll",
      () => {
        if (scrollTimer) return;
        scrollTimer = window.setTimeout(() => {
          window.__gagentBrowserMetrics.scroll_count += 1;
          scrollTimer = null;
          detectUxSignals();
        }, 120);
      },
      true,
    );

    try {
      const clsObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput) {
            window.__gagentBrowserMetrics.cumulative_layout_shift +=
              entry.value;
          }
        }
      });

      clsObserver.observe({
        type: "layout-shift",
        buffered: true,
      });
    } catch (error) {}

    try {
      const eventObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          window.__gagentBrowserMetrics.max_event_duration = Math.max(
            window.__gagentBrowserMetrics.max_event_duration,
            entry.duration || 0,
          );
        }
      });

      eventObserver.observe({
        type: "event",
        buffered: true,
        durationThreshold: 16,
      });
    } catch (error) {}
  });
}

async function getNavigationTiming(page) {
  return page.evaluate(() => {
    const nav = performance.getEntriesByType("navigation")[0];
    if (!nav)
      return {
        page_load_time_ms: 0,
        dom_content_loaded_ms: 0,
        time_to_first_byte_ms: 0,
      };
    return {
      page_load_time_ms: Math.max(0, nav.loadEventEnd - nav.startTime),
      dom_content_loaded_ms: Math.max(
        0,
        nav.domContentLoadedEventEnd - nav.startTime,
      ),
      time_to_first_byte_ms: Math.max(0, nav.responseStart - nav.requestStart),
    };
  });
}

async function getBrowserMetrics(page) {
  return page.evaluate(
    () =>
      window.__gagentBrowserMetrics || {
        click_count: 0,
        scroll_count: 0,
        keyboard_count: 0,
        cumulative_layout_shift: 0,
        max_event_duration: 0,
      },
  );
}

async function detectPageSignals(page) {
  const data = await page.evaluate(() => {
    const metrics = window.__gagentBrowserMetrics || {};

    function isVisibleElement(element) {
      if (!element) return false;
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        style.opacity !== "0" &&
        box.width > 0 &&
        box.height > 0
      );
    }

    const popupElement = document.querySelector(
      '[data-popup="true"], #modalOverlay, .modal-backdrop, .modal-card',
    );

    const cookieElement = document.querySelector(
      '[data-cookie-banner="true"], #cookieBanner, .cookie-banner',
    );

    const overlayElement = document.querySelector(
      '[data-overlay="true"], #blockingOverlay, #modalOverlay, .blocking-overlay, .modal-backdrop',
    );

    const popupCurrent = isVisibleElement(popupElement) ? 1 : 0;
    const cookieCurrent = isVisibleElement(cookieElement) ? 1 : 0;
    const overlayCurrent = isVisibleElement(overlayElement) ? 1 : 0;

    const message = document.querySelector("#message, .validation-message");
    const status = message?.getAttribute("data-status") || "";
    const messageText = (message?.textContent || "").trim();

    const clarityRaw =
      message?.getAttribute("data-error-message-clarity") ||
      document.body.getAttribute("data-error-message-clarity") ||
      "-1";

    return {
      popup_detected: Number(metrics.popup_detected || popupCurrent || 0),
      cookie_banner_detected: Number(
        metrics.cookie_banner_detected || cookieCurrent || 0,
      ),
      overlay_exists: Number(metrics.overlay_detected || overlayCurrent || 0),
      error_message_present:
        status === "error" ||
        /problem|missing|incorrect|blocked|no useful|not complete|error|invalid|required/i.test(
          messageText,
        )
          ? 1
          : 0,
      error_message_clarity: Number(clarityRaw),
    };
  });

  return data;
}

async function isTargetObstructed(page, selector) {
  return page
    .evaluate((targetSelector) => {
      const target = document.querySelector(targetSelector);
      if (!target) return 0;
      const box = target.getBoundingClientRect();
      if (box.width === 0 || box.height === 0) return 1;
      const x = Math.min(
        Math.max(box.left + box.width / 2, 1),
        window.innerWidth - 1,
      );
      const y = Math.min(
        Math.max(box.top + box.height / 2, 1),
        window.innerHeight - 1,
      );
      const topElement = document.elementFromPoint(x, y);
      if (!topElement) return 0;
      if (topElement === target || target.contains(topElement)) return 0;
      if (
        topElement.closest(
          '[data-overlay="true"], #cookieBanner, #modalOverlay, #blockingOverlay',
        )
      )
        return 1;
      return 0;
    }, selector)
    .catch(() => 0);
}

async function clickWithPaintTiming(page, selector, state, options = {}) {
  const before = Date.now();
  const paintStart = await page
    .evaluate(() => performance.now())
    .catch(() => 0);
  try {
    await page
      .locator(selector)
      .first()
      .click({
        timeout: options.timeout || 2500,
        force: Boolean(options.force),
      });
  } catch (error) {
    state.failed_clicks += 1;
    state.error_count += 1;
    state.lastError = error.message;
    return { success: false, feedbackDelayMs: 0, interactionToNextPaintMs: 0 };
  }

  const paintEnd = await page
    .evaluate(
      () =>
        new Promise((resolve) => {
          requestAnimationFrame(() =>
            requestAnimationFrame(() => resolve(performance.now())),
          );
        }),
    )
    .catch(() => paintStart);

  let feedbackDelayMs = 0;
  try {
    await page.waitForFunction(
      () => {
        const message = document.querySelector("#message, .validation-message");
        const status = message?.getAttribute("data-status") || "";
        return ["success", "error", "loading"].includes(status);
      },
      { timeout: options.feedbackTimeout || 6500 },
    );
    feedbackDelayMs = Date.now() - before;
  } catch (error) {
    state.error_count += 1;
    feedbackDelayMs = Date.now() - before;
  }

  return {
    success: true,
    feedbackDelayMs,
    interactionToNextPaintMs: Math.max(0, paintEnd - paintStart),
  };
}

async function fillIfVisible(page, selector, value, state) {
  const locator = page.locator(selector).first();

  const visible = await locator.isVisible().catch(() => false);
  if (!visible) {
    state.failed_clicks += 1;
    state.error_count += 1;
    return false;
  }

  try {
    await locator.fill(value, { timeout: 1500 });
    state.action_steps += 1;
    return true;
  } catch (error) {
    state.failed_clicks += 1;
    state.error_count += 1;
    state.lastError = error.message || "Input fill failed";
    return false;
  }
}

async function checkIfVisible(page, selector, state) {
  const locator = page.locator(selector).first();

  const visible = await locator.isVisible().catch(() => false);
  if (!visible) {
    state.failed_clicks += 1;
    state.error_count += 1;
    return false;
  }

  try {
    await locator.check({ timeout: 1500 });
    state.action_steps += 1;
    return true;
  } catch (checkError) {
    try {
      await locator.click({ timeout: 1500 });
      state.action_steps += 1;
      return true;
    } catch (clickError) {
      state.failed_clicks += 1;
      state.error_count += 1;
      state.lastError =
        clickError.message || checkError.message || "Checkbox click failed";
      return false;
    }
  }
}

async function closeObstructionsIfRecoverable(page, state) {
  if (
    await page
      .locator("#acceptCookies")
      .isVisible()
      .catch(() => false)
  ) {
    await page.locator("#acceptCookies").click();
    state.retry_count += 1;
    state.action_steps += 1;
  }
  if (
    await page
      .locator("#closePopup")
      .isVisible()
      .catch(() => false)
  ) {
    await page.locator("#closePopup").click();
    state.retry_count += 1;
    state.action_steps += 1;
  }
}

async function performFlow(page, scenario, state) {
  const type = scenario.scenario_type;
  const flow = scenario.flow_type;

  await page.waitForTimeout(300);

  if (
    [
      "popup_blocking",
      "cookie_banner_blocking",
      "overlay_blocks_cta",
      "disabled_button_blocked_submit",
    ].includes(type)
  ) {
    state.overlay_blocks_cta = Math.max(
      state.overlay_blocks_cta,
      await isTargetObstructed(page, targetSelectorForFlow(flow)),
    );
  }

  if (flow === "landing_navigation") {
    if (["hidden_cta", "wrong_navigation_path"].includes(type)) {
      const fake = await clickWithPaintTiming(page, "#fakeLandingCta", state, {
        feedbackTimeout: 1200,
      });
      if (fake.success) state.unnecessary_clicks += 1;
      state.failed_clicks += 1;
      state.retry_count += 1;
    }
    if (type === "wrong_navigation_path") return;
    await recoverAndClickTarget(page, "#mainCta", state, type);
    return;
  }

  if (flow === "signup") {
    if (type === "hidden_submit_vague_error") {
      const fake = await clickWithPaintTiming(page, "#fakeSignupBtn", state, {
        feedbackTimeout: 1200,
      });
      if (fake.success) state.unnecessary_clicks += 1;
      state.failed_clicks += 1;
      state.retry_count += 1;
    }
    if (
      [
        "extra_form_fields",
        "vague_error_message",
        "hidden_submit_vague_error",
        "layout_shift",
        "disabled_button_blocked_submit",
      ].includes(type)
    ) {
      await clickWithPaintTiming(page, "#submitBtn", state, {
        feedbackTimeout: 1500,
        force: type === "disabled_button_blocked_submit",
      });
      state.retry_count += 1;
    }
    await fillIfVisible(page, "#name", "Phase Three User", state);
    await fillIfVisible(page, "#email", "phase3@example.com", state);
    await fillIfVisible(page, "#password", "Password123!", state);
    await fillIfVisible(
      page,
      "#referral",
      "REF-" + Math.floor(Math.random() * 999),
      state,
    );
    await checkIfVisible(page, "#terms", state);
    await recoverAndClickTarget(page, "#submitBtn", state, type, true);
    return;
  }

  if (flow === "login") {
    if (type === "fake_login_button") {
      const fake = await clickWithPaintTiming(page, "#fakeLoginBtn", state, {
        feedbackTimeout: 1200,
      });
      if (fake.success) state.unnecessary_clicks += 1;
      state.failed_clicks += 1;
      state.retry_count += 1;
    }
    if (["wrong_first_attempt", "form_validation_error"].includes(type)) {
      await fillIfVisible(page, "#email", "user@example.com", state);
      await fillIfVisible(page, "#password", "wrong-password", state);
      await clickWithPaintTiming(page, "#loginBtn", state, {
        feedbackTimeout: 2500,
      });
      state.retry_count += 1;
    }
    await fillIfVisible(page, "#email", "user@example.com", state);
    await fillIfVisible(page, "#password", "Password123!", state);
    await recoverAndClickTarget(page, "#loginBtn", state, type, true);
    return;
  }

  if (flow === "search") {
    if (type === "hidden_search_button") {
      const fake = await clickWithPaintTiming(page, "#fakeSearchBtn", state, {
        feedbackTimeout: 1200,
      });
      if (fake.success) state.unnecessary_clicks += 1;
      state.failed_clicks += 1;
      state.retry_count += 1;
    }
    if (type === "unclear_search_control") {
      state.unnecessary_clicks += 1;
      state.retry_count += 1;
    }
    await fillIfVisible(page, "#searchInput", "laptop", state);
    await recoverAndClickTarget(page, "#searchBtn", state, type);
    return;
  }

  if (flow === "cta_click") {
    if (["hidden_cta", "indirect_cta_text"].includes(type)) {
      const fake = await clickWithPaintTiming(page, "#fakeCtaBtn", state, {
        feedbackTimeout: 1200,
      });
      if (fake.success) state.unnecessary_clicks += 1;
      state.failed_clicks += 1;
      state.retry_count += 1;
    }
    await recoverAndClickTarget(page, "#ctaBtn", state, type);
  }
}

function targetSelectorForFlow(flow) {
  if (flow === "landing_navigation") return "#mainCta";
  if (flow === "signup") return "#submitBtn";
  if (flow === "login") return "#loginBtn";
  if (flow === "search") return "#searchBtn";
  return "#ctaBtn";
}

async function recoverAndClickTarget(
  page,
  selector,
  state,
  type,
  submitForm = false,
) {
  if (
    [
      "hidden_cta",
      "hidden_search_button",
      "hidden_submit_vague_error",
      "hidden_cta_requires_scroll",
      "fake_login_button",
    ].includes(type)
  ) {
    await page.mouse.wheel(0, 900);
    state.action_steps += 1;
  }

  await closeObstructionsIfRecoverable(page, state);
  state.overlay_blocks_cta = Math.max(
    state.overlay_blocks_cta,
    await isTargetObstructed(page, selector),
  );

  const locator = page.locator(selector).first();
  if (!(await locator.isVisible().catch(() => false))) {
    state.failed_clicks += 1;
    state.error_count += 1;
    return;
  }

  if (submitForm) {
    const formSelector =
      selector === "#submitBtn" ? "#signupForm" : "#loginForm";
    const before = Date.now();
    try {
      await page.locator(formSelector).evaluate((form) => form.requestSubmit());
      await page.waitForFunction(
        () => {
          const message = document.querySelector(
            "#message, .validation-message",
          );
          const status = message?.getAttribute("data-status") || "";
          return ["success", "error", "loading"].includes(status);
        },
        { timeout: 6500 },
      );
      state.feedback_delay_ms = Math.max(
        state.feedback_delay_ms,
        Date.now() - before,
      );
    } catch (error) {
      state.error_count += 1;
      state.feedback_delay_ms = Math.max(
        state.feedback_delay_ms,
        Date.now() - before,
      );
    }
  } else {
    const result = await clickWithPaintTiming(page, selector, state, {
      force: type === "small_click_target",
    });
    state.feedback_delay_ms = Math.max(
      state.feedback_delay_ms,
      result.feedbackDelayMs,
    );
    state.interaction_to_next_paint_ms = Math.max(
      state.interaction_to_next_paint_ms,
      result.interactionToNextPaintMs,
    );
  }

  try {
    await page.waitForFunction(
      () => {
        const message = document.querySelector("#message, .validation-message");
        return (
          message?.getAttribute("data-status") === "success" ||
          message?.getAttribute("data-status") === "error"
        );
      },
      { timeout: 7200 },
    );
  } catch (error) {
    state.error_count += 1;
  }
}

async function determineTaskCompleted(page, scenario) {
  const status = await page
    .locator("#message")
    .getAttribute("data-status")
    .catch(() => null);
  if (scenario.scenario_type === "no_result_error") return 0;
  return status === "success" ? 1 : 0;
}

async function runPhase3Scenario(page, scenario, options) {
  const mode = options.mode || "pilot";
  const viewportType = options.viewportType || "desktop";
  const networkCondition = options.networkCondition || "normal";
  const repeatIndex = Number(options.repeatIndex || 0);
  const viewport = VIEWPORTS[viewportType] || VIEWPORTS.desktop;
  const network = NETWORK_PROFILES[networkCondition] || NETWORK_PROFILES.normal;

  const deterministicRunKey = `${scenario.route}|${viewportType}|${networkCondition}|${repeatIndex}`;

  if (
    (process.env.GAGENT_RESUME === "1" || process.env.GAGENT_APPEND === "1") &&
    COMPLETED_RUN_KEYS.has(deterministicRunKey)
  ) {
    return null;
  }

  const runId = createRunId(
    scenario,
    viewportType,
    networkCondition,
    repeatIndex,
  );
  const seed = `${runId}_${repeatIndex}`;
  const start = Date.now();

  await page.setViewportSize(viewport);
  await setupBrowserMetricListeners(page);

  const state = {
    retry_count: 0,
    error_count: 0,
    failed_clicks: 0,
    unnecessary_clicks: 0,
    action_steps: 0,
    feedback_delay_ms: 0,
    interaction_to_next_paint_ms: 0,
    overlay_blocks_cta: 0,
    lastError: "",
  };

  const baseURL = process.env.GAGENT_BASE_URL || "http://localhost:3000";
  const url = `${baseURL}${scenario.route}?seed=${encodeURIComponent(seed)}&network=${encodeURIComponent(networkCondition)}`;

  if (network.extraDelayMs > 0) {
    await page.waitForTimeout(network.extraDelayMs);
  }

  try {
    await page.goto(url, {
      waitUntil: "domcontentloaded",
      timeout: 20000,
    });
  } catch (navigationError) {
    state.task_completed = 0;
    state.task_failed = 1;
    state.error_count += 1;
    state.failed_clicks += 1;
    state.path_deviation_score += 3;
    state.lastError = navigationError.message || "Navigation timeout";

    try {
      await performFlow(page, scenario, state);
      await page.goto(url, {
        waitUntil: "commit",
        timeout: 8000,
      });
    } catch (fallbackError) {
      state.lastError = fallbackError.message || state.lastError;
    }
  }

  if (network.extraDelayMs > 0) {
    await page.waitForTimeout(network.extraDelayMs);
  }

  try {
    await performFlow(page, scenario, state);
  } catch (flowError) {
    state.task_completed = 0;
    state.task_failed = 1;
    state.error_count += 1;
    state.failed_clicks += 1;
    state.lastError = flowError.message || "Flow execution failed";
  }

  await page.waitForTimeout(250).catch(() => {});

  await performFlow(page, scenario, state);
  await page.waitForTimeout(250);

  const browserMetrics = await getBrowserMetrics(page);
  const navTiming = await getNavigationTiming(page);
  const signals = await detectPageSignals(page);
  const taskCompleted = await determineTaskCompleted(page, scenario);
  const completionTimeSeconds = (Date.now() - start) / 1000;

  const taskFailed = taskCompleted ? 0 : 1;
  const pathDeviationScore = Math.min(
    10,
    state.unnecessary_clicks * 2 +
      state.retry_count * 2 +
      state.failed_clicks * 2 +
      taskFailed * 3,
  );

  const rawRow = {
    flow_type: scenario.flow_type,
    scenario_type: scenario.scenario_type,
    viewport_type: viewportType,
    network_condition: networkCondition,
    task_completed: taskCompleted,
    task_failed: taskFailed,
    completion_time: round(completionTimeSeconds, 3),
    click_count: Number(browserMetrics.click_count || 0),
    scroll_count: Number(browserMetrics.scroll_count || 0),
    keyboard_count: Number(browserMetrics.keyboard_count || 0),
    retry_count: Number(state.retry_count || 0),
    error_count:
      Number(state.error_count || 0) +
      Number(signals.error_message_present || 0),
    failed_clicks: Number(state.failed_clicks || 0),
    unnecessary_clicks: Number(state.unnecessary_clicks || 0),
    path_deviation_score: pathDeviationScore,
    page_load_time_ms: round(navTiming.page_load_time_ms),
    dom_content_loaded_ms: round(navTiming.dom_content_loaded_ms),
    time_to_first_byte_ms: round(navTiming.time_to_first_byte_ms),
    feedback_delay_ms: round(state.feedback_delay_ms),
    interaction_to_next_paint_ms: round(
      Math.max(
        state.interaction_to_next_paint_ms,
        browserMetrics.max_event_duration || 0,
      ),
    ),
    cumulative_layout_shift: round(browserMetrics.cumulative_layout_shift, 4),
    error_message_present: Number(signals.error_message_present || 0),
    error_message_clarity: Number.isFinite(signals.error_message_clarity)
      ? signals.error_message_clarity
      : -1,
    popup_detected: Number(signals.popup_detected || 0),
    cookie_banner_detected: Number(signals.cookie_banner_detected || 0),
    overlay_blocks_cta: Number(
      Math.max(state.overlay_blocks_cta, signals.overlay_exists || 0),
    ),
  };

  const labelled = labelRow(rawRow, scenario.expected_friction_level);
  const datasetRow = Object.fromEntries(
    DATASET_COLUMNS.map((column) => [column, labelled[column]]),
  );
  const auditRow = {
    ...labelled,
    route: scenario.route,
    seed,
    run_id: runId,
  };

  const paths = outputPaths(mode);
  appendCsv(paths.dataset, DATASET_COLUMNS, datasetRow);
  appendCsv(paths.auditRows, AUDIT_COLUMNS, auditRow);

  ensureDir(LOG_ROOT);
  fs.writeFileSync(
    path.join(LOG_ROOT, `${runId}.json`),
    JSON.stringify({ scenario, auditRow, lastError: state.lastError }, null, 2),
  );

  return datasetRow;
}

module.exports = {
  DATASET_COLUMNS,
  VIEWPORTS,
  NETWORK_PROFILES,
  initializeOutput,
  runPhase3Scenario,
  outputPaths,
};
