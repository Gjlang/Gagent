const fs = require("fs");
const path = require("path");
const { chromium } = require("@playwright/test");

const {
  VIEWPORTS,
  NETWORK_PROFILES,
  createEmptyMetrics,
  setupBrowserMetricListeners,
  getNavigationTiming,
  getBrowserMetrics,
  detectPageSignals,
  scrollPage,
  drawGAgentAnnotations,
  clearGAgentAnnotations,
  mergeMetrics,
  round,
} = require("../utils/live-metrics-collector");

const SEARCH_SELECTORS = [
  'input[type="search"]',
  'input[name*="search" i]',
  'input[placeholder*="search" i]',
  'input[aria-label*="search" i]',
  '[role="searchbox"]',
];

const CLICKABLES =
  'button, a[role="button"], input[type="button"], ' +
  'input[type="submit"], a, [aria-label]';

const SAFE_CTA = [
  "learn more",
  "read more",
  "view more",
  "view all",
  "see more",
  "see all",
  "shop now",
  "explore",
  "discover",
  "browse",
  "get started",
  "start now",
  "find out more",
];

const LOGIN_KEYWORDS = ["login", "log in", "sign in", "my account"];

const MENU_KEYWORDS = ["menu", "categories", "category", "shop by category"];

const BLOCKED_ACTIONS = [
  "buy",
  "checkout",
  "order",
  "pay",
  "purchase",
  "delete",
  "remove",
  "logout",
  "sign out",
  "submit",
  "send",
  "download",
  "add to cart",
  "book now",
  "confirm",
  "subscribe",
  "bid",
  "offer",
];

function parseArgs(argv) {
  const result = {};

  for (let index = 2; index < argv.length; index += 1) {
    const current = argv[index];

    if (!current.startsWith("--")) {
      continue;
    }

    const key = current.slice(2);
    const next = argv[index + 1];

    if (!next || next.startsWith("--")) {
      result[key] = true;
    } else {
      result[key] = next;
      index += 1;
    }
  }

  return result;
}

function validUrl(value) {
  try {
    return ["http:", "https:"].includes(new URL(value).protocol);
  } catch {
    return false;
  }
}

function printOutput(payload, exitCode = 0) {
  process.stdout.write(JSON.stringify(payload, null, 2));
  process.exit(exitCode);
}

async function applyNetwork(context, page, networkCondition) {
  const profile = NETWORK_PROFILES[networkCondition];

  if (!profile) {
    return;
  }

  const client = await context.newCDPSession(page);

  await client.send("Network.enable");
  await client.send("Network.emulateNetworkConditions", profile);
  await client.detach();
}

async function openPage(context, targetUrl, networkCondition, timeoutMs) {
  const page = await context.newPage();

  page.setDefaultTimeout(10000);
  page.setDefaultNavigationTimeout(timeoutMs);

  await setupBrowserMetricListeners(page);
  await applyNetwork(context, page, networkCondition);

  await page.goto(targetUrl, {
    waitUntil: "domcontentloaded",
    timeout: timeoutMs,
  });

  await page.waitForTimeout(1000);

  return page;
}

async function findFirstVisible(page, selectors) {
  for (const selector of selectors) {
    const locator = page.locator(selector).first();

    try {
      const exists = (await locator.count()) > 0;
      const visible =
        exists &&
        (await locator.isVisible({
          timeout: 700,
        }));

      if (visible) {
        return locator;
      }
    } catch {
      // Try the next selector.
    }
  }

  return null;
}

async function findKeywordClickable(page, keywords, blockedKeywords = []) {
  const found = await page.evaluate(
    ({ selector, keywords, blockedKeywords }) => {
      function isVisible(element) {
        const style = window.getComputedStyle(element);
        const box = element.getBoundingClientRect();

        return (
          style.display !== "none" &&
          style.visibility !== "hidden" &&
          style.opacity !== "0" &&
          box.width > 24 &&
          box.height > 17
        );
      }

      function getText(element) {
        return String(
          element.innerText ||
            element.value ||
            element.getAttribute("aria-label") ||
            element.getAttribute("title") ||
            "",
        )
          .trim()
          .toLowerCase();
      }

      const candidates = Array.from(document.querySelectorAll(selector))
        .map((element, index) => ({
          element,
          index,
          text: getText(element),
        }))
        .filter((item) => isVisible(item.element))
        .filter((item) =>
          keywords.some((keyword) => item.text.includes(keyword)),
        )
        .filter(
          (item) =>
            !blockedKeywords.some((keyword) => item.text.includes(keyword)),
        )
        .map((item) => ({
          index: item.index,
          text: item.text,
          score: keywords.reduce((total, keyword) => {
            if (item.text === keyword) {
              return total + 100;
            }

            if (item.text.includes(keyword)) {
              return total + 50;
            }

            return total;
          }, 0),
        }))
        .sort((first, second) => second.score - first.score);

      return candidates[0] || null;
    },
    {
      selector: CLICKABLES,
      keywords,
      blockedKeywords,
    },
  );

  if (!found) {
    return null;
  }

  return {
    locator: page.locator(CLICKABLES).nth(found.index),
    text: found.text,
  };
}

async function findNavigationCandidate(page, flowName) {
  const selectorMap = {
    header_navigation:
      'header a[href], nav a[href], [role="navigation"] a[href]',

    category_navigation:
      '[class*="categor" i] a[href], ' +
      '[id*="categor" i] a[href], ' +
      '[aria-label*="categor" i] a[href]',

    product_navigation:
      "article a[href], " +
      '[class*="product" i] a[href], ' +
      '[class*="item" i] a[href], ' +
      '[class*="card" i] a[href]',
  };

  const selector = selectorMap[flowName];

  if (!selector) {
    return null;
  }

  const candidate = await page.evaluate(
    ({ selector, blockedActions, loginKeywords }) => {
      function isVisible(element) {
        const style = window.getComputedStyle(element);
        const box = element.getBoundingClientRect();

        return (
          style.display !== "none" &&
          style.visibility !== "hidden" &&
          style.opacity !== "0" &&
          box.width >= 24 &&
          box.height >= 16
        );
      }

      function elementText(element) {
        return String(
          element.innerText ||
            element.getAttribute("aria-label") ||
            element.getAttribute("title") ||
            element.querySelector("img")?.getAttribute("alt") ||
            "",
        )
          .replace(/\s+/g, " ")
          .trim()
          .toLowerCase();
      }

      function hasSafeHref(element) {
        const href = element.getAttribute("href") || "";

        if (
          !href ||
          href === "#" ||
          href.startsWith("javascript:") ||
          href.startsWith("mailto:") ||
          href.startsWith("tel:") ||
          element.hasAttribute("download")
        ) {
          return false;
        }

        try {
          const targetUrl = new URL(href, window.location.href);

          return targetUrl.origin === window.location.origin;
        } catch {
          return false;
        }
      }

      const elements = Array.from(document.querySelectorAll(selector));

      const candidates = elements
        .map((element, index) => ({
          element,
          index,
          text: elementText(element),
        }))
        .filter((item) => isVisible(item.element))
        .filter((item) => hasSafeHref(item.element))
        .filter((item) => item.text.length >= 2)
        .filter(
          (item) =>
            !blockedActions.some((keyword) => item.text.includes(keyword)),
        )
        .filter(
          (item) =>
            !loginKeywords.some((keyword) => item.text.includes(keyword)),
        )
        .map((item) => ({
          index: item.index,
          text: item.text,
          score:
            (item.element.querySelector("img") ? 30 : 0) +
            (item.text.length <= 45 ? 20 : 0),
        }))
        .sort((first, second) => second.score - first.score);

      return candidates[0] || null;
    },
    {
      selector,
      blockedActions: BLOCKED_ACTIONS,
      loginKeywords: LOGIN_KEYWORDS,
    },
  );

  if (!candidate) {
    return null;
  }

  return {
    locator: page.locator(selector).nth(candidate.index),

    text: candidate.text,
  };
}

async function findMenuToggle(page) {
  const keywordCandidate = await findKeywordClickable(page, MENU_KEYWORDS, [
    ...BLOCKED_ACTIONS,
    ...LOGIN_KEYWORDS,
  ]);

  if (keywordCandidate) {
    return keywordCandidate;
  }

  const locator = page
    .locator(
      "button[aria-expanded], " +
        "button[aria-haspopup], " +
        '[role="button"][aria-expanded], ' +
        '[role="button"][aria-haspopup]',
    )
    .first();

  try {
    if ((await locator.count()) > 0 && (await locator.isVisible())) {
      return {
        locator,
        text: "menu control",
      };
    }
  } catch {
    return null;
  }

  return null;
}

async function createAnnotation(locator, label) {
  try {
    const box = await locator.evaluate((element) => {
      const rectangle = element.getBoundingClientRect();

      return {
        x: rectangle.left + window.scrollX,
        y: rectangle.top + window.scrollY,
        width: rectangle.width,
        height: rectangle.height,
      };
    });

    return {
      label,
      severity: "Medium",
      ...box,
    };
  } catch {
    return null;
  }
}

async function saveScreenshot({
  page,
  annotations,
  screenshotDirectory,
  testRunId,
  flowName,
  label,
  showBrowser,
}) {
  const validAnnotations = annotations.filter(Boolean);

  if (validAnnotations.length > 0) {
    await drawGAgentAnnotations(page, validAnnotations);

    await page.waitForTimeout(500);
  }

  const filePath = path.join(
    screenshotDirectory,
    `full_audit_${testRunId || "manual"}_${flowName}_${Date.now()}.png`,
  );

  await page.screenshot({
    path: filePath,
    fullPage: true,
  });

  if (showBrowser && validAnnotations.length > 0) {
    await page.waitForTimeout(3500);
  }

  await clearGAgentAnnotations(page);

  return {
    file_path: filePath,
    label,
    flow_key: flowName,
    annotations: validAnnotations,
  };
}

async function collectFinalMetrics(page, metrics, startedAt) {
  const timing = await getNavigationTiming(page).catch(() => ({}));

  const browserMetrics = await getBrowserMetrics(page).catch(() => ({}));

  const signals = await detectPageSignals(page).catch(() => ({}));

  const merged = mergeMetrics(
    metrics,
    browserMetrics,
    timing,
    signals,
    (Date.now() - startedAt) / 1000,
  );

  merged.feedback_delay_ms = round(metrics.feedback_delay_ms || 0);

  merged.failed_clicks = metrics.failed_clicks || 0;

  merged.error_count = metrics.error_count || 0;

  merged.task_completed = metrics.task_completed || 0;

  merged.task_failed = metrics.task_failed || 0;

  return merged;
}

async function detectAvailableFeatures(page) {
  const searchInput = await findFirstVisible(page, SEARCH_SELECTORS);

  const safeCta = await findKeywordClickable(page, SAFE_CTA, [
    ...BLOCKED_ACTIONS,
    ...LOGIN_KEYWORDS,
  ]);

  const menuToggle = await findMenuToggle(page);

  const headerNavigation = await findNavigationCandidate(
    page,
    "header_navigation",
  );

  const categoryNavigation = await findNavigationCandidate(
    page,
    "category_navigation",
  );

  const productNavigation = await findNavigationCandidate(
    page,
    "product_navigation",
  );

  const passwordInput = await findFirstVisible(page, [
    'input[type="password"]',
  ]);

  const loginTrigger = await findKeywordClickable(
    page,
    LOGIN_KEYWORDS,
    BLOCKED_ACTIONS,
  );

  const genericFormDetected = await page.evaluate((searchSelectors) => {
    function isVisible(element) {
      const style = window.getComputedStyle(element);

      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        box.width > 20 &&
        box.height > 20
      );
    }

    return Array.from(document.querySelectorAll("form"))
      .filter(isVisible)
      .some((form) => {
        const hasPassword = Boolean(
          form.querySelector('input[type="password"]'),
        );

        const hasSearch = searchSelectors.some((selector) =>
          Boolean(form.querySelector(selector)),
        );

        return !hasPassword && !hasSearch;
      });
  }, SEARCH_SELECTORS);

  return {
    landing_navigation: true,
    search: Boolean(searchInput),
    cta_click: Boolean(safeCta),
    menu_toggle: Boolean(menuToggle),
    header_navigation: Boolean(headerNavigation),
    category_navigation: Boolean(categoryNavigation),
    product_navigation: Boolean(productNavigation),
    login: Boolean(passwordInput || loginTrigger),
    form_review: Boolean(genericFormDetected),
  };
}

async function runFlow(flowName, configuration) {
  const startedAt = Date.now();

  const modelFlowMap = {
    landing_navigation: "landing_navigation",

    search: "search",

    cta_click: "cta_click",

    menu_toggle: "cta_click",

    header_navigation: "landing_navigation",

    category_navigation: "landing_navigation",

    product_navigation: "landing_navigation",

    login: "login",
  };

  const modelFlow = modelFlowMap[flowName] ?? "landing_navigation";

  const metrics = createEmptyMetrics(modelFlow, configuration.viewportType);

  const screenshots = [];
  let page;

  const labels = {
    landing_navigation: "Page loading and navigation",

    search: "Search",

    cta_click: "Safe CTA",

    menu_toggle: "Menu or category control",

    header_navigation: "Header navigation link",

    category_navigation: "Category navigation link",

    product_navigation: "Product or content card",

    login: "Login form",
  };

  try {
    page = await openPage(
      configuration.context,
      configuration.targetUrl,
      configuration.networkCondition,
      configuration.timeoutMs,
    );

    if (flowName === "landing_navigation") {
      await scrollPage(page);
      await page.waitForTimeout(700);

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: "Full Audit — Page loading and navigation",
          showBrowser: configuration.showBrowser,
        }),
      );
    }

    if (flowName === "search") {
      const searchInput = await findFirstVisible(page, SEARCH_SELECTORS);

      if (!searchInput) {
        throw new Error(
          "Search was detected, but no visible search input was available during the test.",
        );
      }

      const searchAnnotation = await createAnnotation(
        searchInput,
        "Search input tested",
      );

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [searchAnnotation],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: "Full Audit — Search",
          showBrowser: configuration.showBrowser,
        }),
      );

      const actionStartedAt = Date.now();

      await searchInput.fill("test");
      await searchInput.press("Enter");

      await page
        .waitForLoadState("domcontentloaded", {
          timeout: 5000,
        })
        .catch(() => null);

      await page.waitForTimeout(1200);

      metrics.feedback_delay_ms = Date.now() - actionStartedAt;
    }

    if (flowName === "cta_click") {
      const candidate = await findKeywordClickable(page, SAFE_CTA, [
        ...BLOCKED_ACTIONS,
        ...LOGIN_KEYWORDS,
      ]);

      if (!candidate) {
        throw new Error(
          "No safe, non-destructive CTA was available during the test.",
        );
      }

      await candidate.locator.scrollIntoViewIfNeeded();

      const ctaAnnotation = await createAnnotation(
        candidate.locator,
        `Safe CTA tested: ${candidate.text}`,
      );

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [ctaAnnotation],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: "Full Audit — Safe CTA",
          showBrowser: configuration.showBrowser,
        }),
      );

      const actionStartedAt = Date.now();

      await candidate.locator.click({
        timeout: 5000,
      });

      await page
        .waitForLoadState("domcontentloaded", {
          timeout: 5000,
        })
        .catch(() => null);

      await page.waitForTimeout(1200);

      metrics.feedback_delay_ms = Date.now() - actionStartedAt;
    }

    if (flowName === "menu_toggle") {
      const candidate = await findMenuToggle(page);

      if (!candidate) {
        throw new Error("Menu control was no longer visible.");
      }

      await candidate.locator.scrollIntoViewIfNeeded();

      const annotation = await createAnnotation(
        candidate.locator,
        `Menu tested: ${candidate.text}`,
      );

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [annotation],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: "Full Audit — Menu or category control",
          showBrowser: configuration.showBrowser,
        }),
      );

      const actionStartedAt = Date.now();

      await candidate.locator.click({
        timeout: 7000,
      });

      metrics.click_count += 1;

      await page.waitForTimeout(1000);

      await page.keyboard.press("Escape").catch(() => null);

      metrics.feedback_delay_ms = Date.now() - actionStartedAt;
    }

    if (flowName === "login") {
      let passwordInput = await findFirstVisible(page, [
        'input[type="password"]',
      ]);

      if (!passwordInput) {
        const loginTrigger = await findKeywordClickable(
          page,
          LOGIN_KEYWORDS,
          BLOCKED_ACTIONS,
        );

        if (!loginTrigger) {
          throw new Error(
            "Login was detected, but no safe visible login control was available.",
          );
        }

        const triggerAnnotation = await createAnnotation(
          loginTrigger.locator,
          "Login entry point",
        );

        screenshots.push(
          await saveScreenshot({
            page,
            annotations: [triggerAnnotation],
            screenshotDirectory: configuration.screenshotDirectory,
            testRunId: configuration.testRunId,
            flowName,
            label: "Full Audit — Login entry point",
            showBrowser: configuration.showBrowser,
          }),
        );

        await loginTrigger.locator.click({
          timeout: 5000,
        });

        await page.waitForTimeout(1500);

        passwordInput = await findFirstVisible(page, [
          'input[type="password"]',
        ]);
      }

      const identifierInput = await findFirstVisible(page, [
        'input[type="email"]',
        'input[name*="email" i]',
        'input[name*="user" i]',
        'input[autocomplete="username"]',
        'input[type="text"]',
      ]);

      if (!passwordInput || !identifierInput) {
        throw new Error(
          "The login form did not expose both identifier and password fields.",
        );
      }

      const identifierAnnotation = await createAnnotation(
        identifierInput,
        "Login identifier",
      );

      const passwordAnnotation = await createAnnotation(
        passwordInput,
        "Login password",
      );

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [identifierAnnotation, passwordAnnotation],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: "Full Audit — Login form",
          showBrowser: configuration.showBrowser,
        }),
      );

      const actionStartedAt = Date.now();

      await identifierInput.fill("gagent.test@example.com");

      await passwordInput.fill("GAgentTest123!");

      await page.waitForTimeout(600);

      await identifierInput.clear();
      await passwordInput.clear();

      metrics.feedback_delay_ms = Date.now() - actionStartedAt;
    }

    metrics.task_completed = 1;
    metrics.task_failed = 0;
    if (
      [
        "header_navigation",
        "category_navigation",
        "product_navigation",
      ].includes(flowName)
    ) {
      const candidate = await findNavigationCandidate(page, flowName);

      if (!candidate) {
        throw new Error(`${labels[flowName]} was no longer available.`);
      }

      await candidate.locator.scrollIntoViewIfNeeded();

      const annotation = await createAnnotation(
        candidate.locator,
        `${labels[flowName]} tested: ${candidate.text}`,
      );

      screenshots.push(
        await saveScreenshot({
          page,
          annotations: [annotation],
          screenshotDirectory: configuration.screenshotDirectory,
          testRunId: configuration.testRunId,
          flowName,
          label: `Full Audit — ${labels[flowName]}`,
          showBrowser: configuration.showBrowser,
        }),
      );

      const actionStartedAt = Date.now();

      await candidate.locator.click({
        timeout: 7000,
      });

      metrics.click_count += 1;

      await page
        .waitForLoadState("domcontentloaded", {
          timeout: 7000,
        })
        .catch(() => null);

      await page.waitForTimeout(1200);

      metrics.feedback_delay_ms = Date.now() - actionStartedAt;
    }
    return {
      audit_flow: flowName,
      flow_type: modelFlow,
      label: labels[flowName],
      detected: true,
      status: "passed",
      reason:
        flowName === "login"
          ? "Login fields accepted safe dummy input. The form was not submitted."
          : "Flow completed successfully.",
      metrics: await collectFinalMetrics(page, metrics, startedAt),
      screenshots,
    };
  } catch (error) {
    metrics.error_count += 1;

    if (["search", "cta_click"].includes(flowName)) {
      metrics.failed_clicks += 1;
    }

    metrics.task_completed = 0;
    metrics.task_failed = 1;

    const failedMetrics = page
      ? await collectFinalMetrics(page, metrics, startedAt)
      : {
          ...metrics,
          completion_time: round((Date.now() - startedAt) / 1000, 2),
        };

    return {
      audit_flow: flowName,
      flow_type: modelFlow,
      label: labels[flowName],
      detected: true,
      status: "failed",
      reason: error.message,
      metrics: failedMetrics,
      screenshots,
    };
  } finally {
    if (page) {
      await page.close().catch(() => null);
    }
  }
}

async function main() {
  const args = parseArgs(process.argv);

  const targetUrl = args.url;
  const viewportType = args.viewport || "desktop";

  const networkCondition = args.network || "normal";

  const testRunId = args.testRunId || null;

  const maxDurationSeconds = Number(args.maxDuration || 180);

  const showBrowser = String(args.headed || "0") === "1";

  const slowMoMs = Math.max(0, Math.min(Number(args.slowMo || 0), 1000));

  if (!validUrl(targetUrl)) {
    printOutput(
      {
        status: "error",
        message: "Invalid URL.",
        test_run_id: testRunId,
      },
      2,
    );
  }

  if (!VIEWPORTS[viewportType]) {
    printOutput(
      {
        status: "error",
        message: "Invalid viewport.",
        test_run_id: testRunId,
      },
      2,
    );
  }

  if (!["normal", "slow"].includes(networkCondition)) {
    printOutput(
      {
        status: "error",
        message: "Invalid network condition.",
        test_run_id: testRunId,
      },
      2,
    );
  }

  const timeoutMs = Math.max(30, Math.min(maxDurationSeconds, 300)) * 1000;

  const outputDirectory = path.resolve(__dirname, "../outputs/live_tests");

  const screenshotDirectory = path.join(outputDirectory, "screenshots");

  fs.mkdirSync(screenshotDirectory, {
    recursive: true,
  });

  let browser;

  const hardTimeout = setTimeout(() => {
    printOutput(
      {
        status: "error",
        message: `Full audit exceeded ${maxDurationSeconds} seconds.`,
        test_run_id: testRunId,
      },
      124,
    );
  }, timeoutMs + 5000);

  try {
    browser = await chromium.launch({
      headless: !showBrowser,
      slowMo: showBrowser ? slowMoMs : 0,
    });

    const context = await browser.newContext({
      viewport: VIEWPORTS[viewportType],

      ignoreHTTPSErrors: true,
      acceptDownloads: false,

      userAgent: "Mozilla/5.0 GAgentFullAudit/1.0",
    });

    const detectorPage = await openPage(
      context,
      targetUrl,
      networkCondition,
      timeoutMs,
    );

    const detectedFeatures = await detectAvailableFeatures(detectorPage);

    await detectorPage.close();

    const configuration = {
      context,
      targetUrl,
      networkCondition,
      timeoutMs,
      viewportType,
      screenshotDirectory,
      testRunId,
      showBrowser,
    };

    const flowResults = [];

    flowResults.push(await runFlow("landing_navigation", configuration));

    if (detectedFeatures.search) {
      flowResults.push(await runFlow("search", configuration));
    }

    if (detectedFeatures.cta_click) {
      flowResults.push(await runFlow("cta_click", configuration));
    }

    if (detectedFeatures.menu_toggle) {
      flowResults.push(await runFlow("menu_toggle", configuration));
    }

    if (detectedFeatures.header_navigation) {
      flowResults.push(await runFlow("header_navigation", configuration));
    }

    if (detectedFeatures.category_navigation) {
      flowResults.push(await runFlow("category_navigation", configuration));
    }

    if (detectedFeatures.product_navigation) {
      flowResults.push(await runFlow("product_navigation", configuration));
    }

    if (detectedFeatures.login) {
      flowResults.push(await runFlow("login", configuration));
    }
    if (detectedFeatures.form_review) {
      flowResults.push({
        audit_flow: "form_review",
        flow_type: "form_review",
        label: "Generic form",
        detected: true,
        status: "skipped",
        reason:
          "A form was detected, but it was not submitted because it may send real data or trigger a destructive action.",
        metrics: null,
        screenshots: [],
      });
    }

    const screenshots = flowResults.flatMap(
      (result) => result.screenshots || [],
    );

    const auditSummary = {
      tested_count: flowResults.filter((result) => result.status !== "skipped")
        .length,

      passed_count: flowResults.filter((result) => result.status === "passed")
        .length,

      failed_count: flowResults.filter((result) => result.status === "failed")
        .length,

      skipped_count: flowResults.filter((result) => result.status === "skipped")
        .length,
    };

    const fallbackMetrics =
      flowResults.find((result) => result.status === "failed" && result.metrics)
        ?.metrics || flowResults.find((result) => result.metrics)?.metrics;

    const rawMetricsPath = path.join(
      outputDirectory,
      `full_audit_${testRunId || "manual"}_${Date.now()}.json`,
    );

    const payload = {
      status: "success",

      message:
        `Full audit completed: ` +
        `${auditSummary.passed_count} passed, ` +
        `${auditSummary.failed_count} failed, ` +
        `${auditSummary.skipped_count} skipped.`,

      test_run_id: testRunId,
      target_url: targetUrl,
      flow_type: "full_audit",
      audit_mode: "full_audit",
      viewport_type: viewportType,
      network_condition: networkCondition,

      detected_features: detectedFeatures,

      audit_summary: auditSummary,

      flow_results: flowResults,

      screenshots,

      metrics: fallbackMetrics,

      raw_metrics_path: rawMetricsPath,
    };

    fs.writeFileSync(rawMetricsPath, JSON.stringify(payload, null, 2), "utf8");

    clearTimeout(hardTimeout);
    await browser.close();

    printOutput(payload);
  } catch (error) {
    if (browser) {
      await browser.close().catch(() => null);
    }

    clearTimeout(hardTimeout);

    printOutput(
      {
        status: "error",
        message: error.message,
        test_run_id: testRunId,
        flow_type: "full_audit",
      },
      1,
    );
  }
}

main();
