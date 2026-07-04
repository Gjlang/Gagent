const fs = require("fs");
const os = require("os");
const path = require("path");

const fallbackTempDir = path.resolve(__dirname, "../outputs/playwright-temp");
fs.mkdirSync(fallbackTempDir, { recursive: true });

if (
  !process.env.TEMP ||
  process.env.TEMP.includes("undefined") ||
  !process.env.TMP ||
  process.env.TMP.includes("undefined")
) {
  process.env.TEMP = fallbackTempDir;
  process.env.TMP = fallbackTempDir;
  process.env.TMPDIR = fallbackTempDir;
}

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
  clickBestCTA,
  runBasicSearch,
  mergeMetrics,
  round,
} = require("../utils/live-metrics-collector");

function parseArgs(argv) {
  const args = {};

  for (let i = 2; i < argv.length; i += 1) {
    const item = argv[i];

    if (item.startsWith("--")) {
      const key = item.replace(/^--/, "");
      const value = argv[i + 1];

      if (!value || value.startsWith("--")) {
        args[key] = true;
      } else {
        args[key] = value;
        i += 1;
      }
    }
  }

  return args;
}

function isAllowedUrl(url) {
  try {
    const parsed = new URL(url);
    return ["http:", "https:"].includes(parsed.protocol);
  } catch (error) {
    return false;
  }
}

function printJsonAndExit(payload, code = 0) {
  process.stdout.write(JSON.stringify(payload, null, 2));
  process.exit(code);
}

async function applyNetworkProfile(context, networkCondition) {
  const profile = NETWORK_PROFILES[networkCondition];

  if (!profile) {
    return;
  }

  const client = await context.newCDPSession(await context.newPage());
  await client.send("Network.enable");
  await client.send("Network.emulateNetworkConditions", profile);
  await client.detach();
}

async function main() {
  const args = parseArgs(process.argv);

  const targetUrl = args.url;
  const flowType = args.flow || "landing_navigation";
  const viewportType = args.viewport || "desktop";
  const networkCondition = args.network || "normal";
  const testRunId = args.testRunId || null;
  const maxDurationSeconds = Number(args.maxDuration || 60);

  const allowedFlows = ["landing_navigation", "cta_click", "basic_search"];
  const allowedViewports = ["desktop", "tablet", "mobile"];
  const allowedNetworks = ["normal", "slow"];

  if (!targetUrl || !isAllowedUrl(targetUrl)) {
    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: "Invalid URL. Only http and https URLs are allowed.",
      },
      2,
    );
  }

  if (!allowedFlows.includes(flowType)) {
    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: `Invalid flow. Allowed values: ${allowedFlows.join(", ")}`,
      },
      2,
    );
  }

  if (!allowedViewports.includes(viewportType)) {
    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: `Invalid viewport. Allowed values: ${allowedViewports.join(", ")}`,
      },
      2,
    );
  }

  if (!allowedNetworks.includes(networkCondition)) {
    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: `Invalid network condition. Allowed values: ${allowedNetworks.join(", ")}`,
      },
      2,
    );
  }

  const startedAt = Date.now();
  const metrics = createEmptyMetrics(flowType, viewportType);
  let browser;

  const timeoutMs = Math.max(10, Math.min(maxDurationSeconds, 120)) * 1000;

  const hardTimeout = setTimeout(() => {
    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: `Live Playwright test exceeded max duration of ${maxDurationSeconds} seconds.`,
        metrics,
      },
      124,
    );
  }, timeoutMs + 3000);

  try {
    browser = await chromium.launch({
      headless: true,
    });

    const context = await browser.newContext({
      viewport: VIEWPORTS[viewportType],
      ignoreHTTPSErrors: true,
      userAgent:
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 GAgentLiveTester/1.0",
    });

    const page = await context.newPage();
    page.setDefaultTimeout(10000);
    page.setDefaultNavigationTimeout(timeoutMs);

    await setupBrowserMetricListeners(page);

    if (networkCondition === "slow") {
      try {
        const client = await context.newCDPSession(page);
        await client.send("Network.enable");
        await client.send(
          "Network.emulateNetworkConditions",
          NETWORK_PROFILES.slow,
        );
      } catch (error) {
        metrics.error_count += 1;
      }
    }

    try {
      await page.goto(targetUrl, {
        waitUntil: "domcontentloaded",
        timeout: timeoutMs,
      });
    } catch (error) {
      metrics.error_count += 1;
      metrics.error_message_present = 1;
      metrics.error_message_clarity = 1;

      const timing = await getNavigationTiming(page).catch(() => ({
        page_load_time_ms: 0,
        dom_content_loaded_ms: 0,
        time_to_first_byte_ms: 0,
      }));

      const browserMetrics = await getBrowserMetrics(page).catch(() => ({}));
      const signals = await detectPageSignals(page).catch(() => ({}));

      const runtimeSeconds = (Date.now() - startedAt) / 1000;

      const finalMetrics = mergeMetrics(
        metrics,
        browserMetrics,
        timing,
        signals,
        runtimeSeconds,
      );

      clearTimeout(hardTimeout);

      printJsonAndExit(
        {
          status: "error",
          test_run_id: testRunId,
          message: `Page load failed: ${error.message}`,
          target_url: targetUrl,
          flow_type: flowType,
          viewport_type: viewportType,
          network_condition: networkCondition,
          metrics: finalMetrics,
        },
        1,
      );
    }

    await page.waitForTimeout(1000);

    let taskReason = "Task not completed.";

    if (flowType === "landing_navigation") {
      await scrollPage(page);
      await page.waitForTimeout(750);

      metrics.task_completed = 1;
      metrics.task_failed = 0;
      taskReason = "Landing page loaded and inspected.";
    }

    if (flowType === "cta_click") {
      await scrollPage(page);
      const clickResult = await clickBestCTA(page);

      metrics.feedback_delay_ms = clickResult.feedback_delay_ms;
      metrics.failed_clicks += clickResult.failed_clicks;

      if (clickResult.clicked) {
        metrics.task_completed = 1;
        metrics.task_failed = 0;
      } else {
        metrics.task_completed = 0;
        metrics.task_failed = 1;
        metrics.error_count += 1;
      }

      taskReason = clickResult.reason;
    }

    if (flowType === "basic_search") {
      const searchResult = await runBasicSearch(page);

      metrics.feedback_delay_ms = searchResult.feedback_delay_ms;

      if (searchResult.searched) {
        metrics.task_completed = 1;
        metrics.task_failed = 0;
      } else {
        metrics.task_completed = 0;
        metrics.task_failed = 1;
        metrics.error_count += 1;
        metrics.error_message_clarity = 0;
      }

      taskReason = searchResult.reason;
    }

    const timing = await getNavigationTiming(page);
    const browserMetrics = await getBrowserMetrics(page);
    const signals = await detectPageSignals(page);

    if (signals.captcha_detected) {
      metrics.task_completed = 0;
      metrics.task_failed = 1;
      metrics.error_count += 1;
      metrics.error_message_present = 1;
      metrics.error_message_clarity = 2;
      taskReason =
        "CAPTCHA or human verification detected. GAgent did not bypass it.";
    }

    if (
      signals.large_overlay_detected &&
      flowType === "cta_click" &&
      metrics.task_completed === 0
    ) {
      metrics.overlay_blocks_cta = 1;
    }

    const runtimeSeconds = (Date.now() - startedAt) / 1000;
    const finalMetrics = mergeMetrics(
      metrics,
      browserMetrics,
      timing,
      signals,
      runtimeSeconds,
    );

    finalMetrics.feedback_delay_ms = round(
      metrics.feedback_delay_ms || finalMetrics.feedback_delay_ms || 0,
      0,
    );
    finalMetrics.failed_clicks = metrics.failed_clicks;
    finalMetrics.error_count = metrics.error_count;
    finalMetrics.task_completed = metrics.task_completed;
    finalMetrics.task_failed = metrics.task_failed;
    finalMetrics.overlay_blocks_cta = metrics.overlay_blocks_cta;

    const outputDir = path.resolve(__dirname, "../outputs/live_tests");
    fs.mkdirSync(outputDir, { recursive: true });

    const outputPath = path.join(
      outputDir,
      `live_test_${testRunId || "manual"}_${Date.now()}.json`,
    );

    const output = {
      status: "success",
      test_run_id: testRunId,
      message: taskReason,
      target_url: targetUrl,
      flow_type: flowType,
      viewport_type: viewportType,
      network_condition: networkCondition,
      raw_metrics_path: outputPath,
      metrics: finalMetrics,
    };

    fs.writeFileSync(outputPath, JSON.stringify(output, null, 2), "utf8");

    clearTimeout(hardTimeout);
    await browser.close();

    printJsonAndExit(output, 0);
  } catch (error) {
    const runtimeSeconds = (Date.now() - startedAt) / 1000;

    metrics.completion_time = round(runtimeSeconds, 2);
    metrics.task_completed = 0;
    metrics.task_failed = 1;
    metrics.error_count += 1;
    metrics.error_message_present = 1;
    metrics.error_message_clarity = 1;

    if (browser) {
      await browser.close().catch(() => null);
    }

    clearTimeout(hardTimeout);

    printJsonAndExit(
      {
        status: "error",
        test_run_id: testRunId,
        message: error.message,
        target_url: targetUrl,
        flow_type: flowType,
        viewport_type: viewportType,
        network_condition: networkCondition,
        metrics,
      },
      1,
    );
  }
}

main();
