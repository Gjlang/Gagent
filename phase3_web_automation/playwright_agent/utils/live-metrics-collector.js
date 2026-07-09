const CTA_KEYWORDS = [
  "get started",
  "start",
  "sign up",
  "try now",
  "learn more",
  "contact us",
  "book now",
  "buy now",
  "continue",
  "submit",
  "download",
  "request demo",
];

const VIEWPORTS = {
  desktop: { width: 1366, height: 768 },
  tablet: { width: 768, height: 1024 },
  mobile: { width: 390, height: 844 },
};

const NETWORK_PROFILES = {
  normal: null,
  slow: {
    offline: false,
    downloadThroughput: (750 * 1024) / 8,
    uploadThroughput: (250 * 1024) / 8,
    latency: 300,
  },
};

function round(value, digits = 0) {
  const factor = 10 ** digits;
  return Math.round((Number(value) || 0) * factor) / factor;
}

function createEmptyMetrics(flowType, viewportType) {
  return {
    flow_type: flowType === "basic_search" ? "search" : flowType,
    viewport_type: viewportType,

    task_completed: 0,
    task_failed: 1,

    completion_time: 0,
    click_count: 0,
    scroll_count: 0,
    keyboard_count: 0,
    retry_count: 0,
    error_count: 0,
    failed_clicks: 0,
    unnecessary_clicks: 0,

    path_deviation_score: 0,
    page_load_time_ms: 0,
    dom_content_loaded_ms: 0,
    time_to_first_byte_ms: 0,
    feedback_delay_ms: 0,
    interaction_to_next_paint_ms: 0,
    cumulative_layout_shift: 0,

    error_message_present: 0,
    error_message_clarity: -1,
    popup_detected: 0,
    cookie_banner_detected: 0,
    overlay_blocks_cta: 0,
  };
}

async function setupBrowserMetricListeners(page) {
  await page.addInitScript(() => {
    window.__gagentLiveMetrics = {
      click_count: 0,
      scroll_count: 0,
      keyboard_count: 0,
      cumulative_layout_shift: 0,
      max_event_duration: 0,
    };

    window.addEventListener(
      "click",
      () => {
        window.__gagentLiveMetrics.click_count += 1;
      },
      true,
    );

    window.addEventListener(
      "scroll",
      () => {
        window.__gagentLiveMetrics.scroll_count += 1;
      },
      true,
    );

    window.addEventListener(
      "keydown",
      () => {
        window.__gagentLiveMetrics.keyboard_count += 1;
      },
      true,
    );

    try {
      new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput) {
            window.__gagentLiveMetrics.cumulative_layout_shift +=
              entry.value || 0;
          }
        }
      }).observe({ type: "layout-shift", buffered: true });
    } catch (error) {
      // Layout shift is not supported in every browser context.
    }

    try {
      new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          window.__gagentLiveMetrics.max_event_duration = Math.max(
            window.__gagentLiveMetrics.max_event_duration,
            entry.duration || 0,
          );
        }
      }).observe({ type: "event", buffered: true, durationThreshold: 16 });
    } catch (error) {
      // Event timing is not supported in every browser context.
    }
  });
}

async function getNavigationTiming(page) {
  return await page.evaluate(() => {
    const nav = performance.getEntriesByType("navigation")[0];

    if (!nav) {
      return {
        page_load_time_ms: 0,
        dom_content_loaded_ms: 0,
        time_to_first_byte_ms: 0,
      };
    }

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
  return await page.evaluate(() => window.__gagentLiveMetrics || {});
}

async function detectPageSignals(page) {
  return await page.evaluate(() => {
    const bodyText = (document.body?.innerText || "").toLowerCase();

    const popupWords = [
      "subscribe",
      "newsletter",
      "modal",
      "popup",
      "limited offer",
    ];
    const cookieWords = ["cookie", "cookies", "consent", "privacy preferences"];
    const errorWords = [
      "error",
      "not found",
      "something went wrong",
      "try again",
      "failed",
    ];
    const captchaWords = [
      "captcha",
      "i am not a robot",
      "recaptcha",
      "verify you are human",
    ];

    const visibleElements = Array.from(
      document.querySelectorAll("body *"),
    ).filter((element) => {
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        style.opacity !== "0" &&
        box.width > 0 &&
        box.height > 0
      );
    });

    const fixedLargeElements = visibleElements.filter((element) => {
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();
      const area = box.width * box.height;
      const screenArea = window.innerWidth * window.innerHeight;

      return (
        ["fixed", "sticky"].includes(style.position) &&
        area > screenArea * 0.2 &&
        Number(style.zIndex || 0) >= 10
      );
    });

    const cookieBannerDetected =
      cookieWords.some((word) => bodyText.includes(word)) &&
      visibleElements.some((element) => {
        const text = (element.innerText || "").toLowerCase();
        const style = window.getComputedStyle(element);
        return (
          ["fixed", "sticky"].includes(style.position) &&
          cookieWords.some((word) => text.includes(word))
        );
      });

    return {
      popup_detected: popupWords.some((word) => bodyText.includes(word))
        ? 1
        : 0,
      cookie_banner_detected: cookieBannerDetected ? 1 : 0,
      error_message_present: errorWords.some((word) => bodyText.includes(word))
        ? 1
        : 0,
      captcha_detected: captchaWords.some((word) => bodyText.includes(word))
        ? 1
        : 0,
      large_overlay_detected: fixedLargeElements.length > 0 ? 1 : 0,
    };
  });
}

async function findBestCTA(page) {
  return await page.evaluate((keywords) => {
    function isVisible(element) {
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        style.opacity !== "0" &&
        box.width >= 40 &&
        box.height >= 20
      );
    }

    const candidates = Array.from(
      document.querySelectorAll(
        'button, a[role="button"], input[type="submit"], a, [aria-label]',
      ),
    );

    const scored = candidates
      .filter(isVisible)
      .map((element, index) => {
        const box = element.getBoundingClientRect();
        const text = (
          element.innerText ||
          element.value ||
          element.getAttribute("aria-label") ||
          ""
        )
          .trim()
          .toLowerCase();

        let score = 0;

        if (keywords.some((keyword) => text.includes(keyword))) score += 50;
        if (element.tagName.toLowerCase() === "button") score += 20;
        if (element.getAttribute("role") === "button") score += 15;
        if (element.tagName.toLowerCase() === "input") score += 15;
        if (box.top >= 0 && box.top <= window.innerHeight) score += 15;
        if (box.width >= 100 && box.height >= 35) score += 10;
        if (
          element.disabled ||
          element.getAttribute("aria-disabled") === "true"
        )
          score -= 100;

        return {
          index,
          score,
          text,
          tag: element.tagName.toLowerCase(),
          selectorHint: text,
        };
      })
      .filter((item) => item.score > 0)
      .sort((a, b) => b.score - a.score);

    return scored[0] || null;
  }, CTA_KEYWORDS);
}
async function getLocatorAnnotation(locator, label, severity = "High") {
  try {
    const box = await locator.evaluate((element) => {
      const rect = element.getBoundingClientRect();

      return {
        x: rect.left + window.scrollX,
        y: rect.top + window.scrollY,
        width: rect.width,
        height: rect.height,
        text:
          element.innerText ||
          element.value ||
          element.getAttribute("aria-label") ||
          element.getAttribute("placeholder") ||
          "",
      };
    });

    if (!box || box.width <= 0 || box.height <= 0) {
      return null;
    }

    return {
      label,
      severity,
      x: Math.round(box.x),
      y: Math.round(box.y),
      width: Math.round(box.width),
      height: Math.round(box.height),
      text: String(box.text || "")
        .trim()
        .slice(0, 80),
    };
  } catch (error) {
    return null;
  }
}

async function drawGAgentAnnotations(page, annotations = []) {
  if (!annotations.length) return;

  await page.evaluate((items) => {
    const oldLayer = document.getElementById("__gagent_annotation_layer");
    if (oldLayer) oldLayer.remove();

    const layer = document.createElement("div");
    layer.id = "__gagent_annotation_layer";
    layer.style.position = "absolute";
    layer.style.left = "0";
    layer.style.top = "0";
    layer.style.width = "100%";
    layer.style.height = `${Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)}px`;
    layer.style.zIndex = "2147483647";
    layer.style.pointerEvents = "none";

    items.forEach((item, index) => {
      const box = document.createElement("div");
      box.style.position = "absolute";
      box.style.left = `${item.x}px`;
      box.style.top = `${item.y}px`;
      box.style.width = `${Math.max(item.width, 20)}px`;
      box.style.height = `${Math.max(item.height, 20)}px`;
      box.style.border = "4px solid #ef4444";
      box.style.borderRadius = "10px";
      box.style.boxShadow = "0 0 0 9999px rgba(239, 68, 68, 0.05)";
      box.style.background = "rgba(239, 68, 68, 0.08)";

      const label = document.createElement("div");
      label.textContent = `${index + 1}. ${item.label}`;
      label.style.position = "absolute";
      label.style.left = `${item.x}px`;
      label.style.top = `${Math.max(0, item.y - 34)}px`;
      label.style.background = "#ef4444";
      label.style.color = "#ffffff";
      label.style.padding = "6px 10px";
      label.style.borderRadius = "999px";
      label.style.font = "700 13px Arial, sans-serif";
      label.style.whiteSpace = "nowrap";
      label.style.boxShadow = "0 8px 18px rgba(15, 23, 42, 0.24)";

      layer.appendChild(box);
      layer.appendChild(label);
    });

    document.body.appendChild(layer);
  }, annotations);
}

async function clearGAgentAnnotations(page) {
  await page
    .evaluate(() => {
      const layer = document.getElementById("__gagent_annotation_layer");
      if (layer) layer.remove();
    })
    .catch(() => {});
}

async function clickBestCTA(page) {
  const candidate = await findBestCTA(page);

  if (!candidate) {
    return {
      clicked: false,
      failed_clicks: 1,
      feedback_delay_ms: 0,
      reason: "No suitable CTA candidate found.",
      annotations: [],
    };
  }

  const start = Date.now();

  try {
    const elements = await page
      .locator(
        'button, a[role="button"], input[type="submit"], a, [aria-label]',
      )
      .all();

    const target = elements[candidate.index];

    if (!target) {
      return {
        clicked: false,
        failed_clicks: 1,
        feedback_delay_ms: 0,
        reason: "CTA candidate became unavailable before click.",
        annotations: [],
      };
    }

    const annotation = await getLocatorAnnotation(
      target,
      `Clicked CTA: ${candidate.text || "button"}`,
      "High",
    );

    await target.click({ timeout: 5000 });

    try {
      await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    } catch (error) {
      // Some CTA actions update the page without navigation.
    }

    await page.waitForTimeout(2500);

    return {
      clicked: true,
      failed_clicks: 0,
      feedback_delay_ms: Date.now() - start,
      reason: "CTA clicked.",
      annotations: annotation ? [annotation] : [],
    };
  } catch (error) {
    return {
      clicked: false,
      failed_clicks: 1,
      feedback_delay_ms: Date.now() - start,
      reason: error.message,
      annotations: [],
    };
  }
}

async function runBasicSearch(page) {
  const selectors = [
    'input[type="search"]',
    'input[name*="search" i]',
    'input[placeholder*="search" i]',
    'input[aria-label*="search" i]',
    '[role="searchbox"]',
  ];

  const start = Date.now();

  for (const selector of selectors) {
    const locator = page.locator(selector).first();

    try {
      if (
        (await locator.count()) > 0 &&
        (await locator.isVisible({ timeout: 1500 }))
      ) {
        const annotation = await getLocatorAnnotation(
          locator,
          "Search input tested",
          "Medium",
        );

        await locator.fill("test");
        await locator.press("Enter");

        try {
          await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
        } catch (error) {
          // Search may update dynamically.
        }

        await page.waitForTimeout(2000);

        return {
          searched: true,
          feedback_delay_ms: Date.now() - start,
          reason: "Search input found and submitted.",
          annotations: annotation ? [annotation] : [],
        };
      }
    } catch (error) {
      // Try next selector.
    }
  }

  return {
    searched: false,
    feedback_delay_ms: 0,
    reason: "No search input found.",
    annotations: [],
  };
}

async function scrollPage(page) {
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let total = 0;
      const distance = Math.max(250, Math.floor(window.innerHeight / 2));
      const timer = setInterval(() => {
        window.scrollBy(0, distance);
        total += distance;

        if (total >= document.body.scrollHeight || total >= 2000) {
          clearInterval(timer);
          resolve();
        }
      }, 150);
    });
  });
}

function mergeMetrics(base, browserMetrics, timing, signals, runtimeSeconds) {
  return {
    ...base,
    completion_time: round(runtimeSeconds, 2),
    click_count: Number(browserMetrics.click_count || base.click_count || 0),
    scroll_count: Number(browserMetrics.scroll_count || base.scroll_count || 0),
    keyboard_count: Number(
      browserMetrics.keyboard_count || base.keyboard_count || 0,
    ),
    cumulative_layout_shift: round(
      browserMetrics.cumulative_layout_shift || 0,
      4,
    ),
    interaction_to_next_paint_ms: round(
      browserMetrics.max_event_duration || 0,
      0,
    ),

    page_load_time_ms: round(timing.page_load_time_ms || 0, 0),
    dom_content_loaded_ms: round(timing.dom_content_loaded_ms || 0, 0),
    time_to_first_byte_ms: round(timing.time_to_first_byte_ms || 0, 0),

    error_message_present: signals.error_message_present || 0,
    error_message_clarity: signals.error_message_present ? 1 : -1,
    popup_detected: signals.popup_detected || 0,
    cookie_banner_detected: signals.cookie_banner_detected || 0,
  };
}

async function detectBestFlow(page) {
  return await page.evaluate((ctaKeywords) => {
    function isVisible(element) {
      const style = window.getComputedStyle(element);
      const box = element.getBoundingClientRect();

      return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        style.opacity !== "0" &&
        box.width >= 30 &&
        box.height >= 20
      );
    }

    const searchSelectors = [
      'input[type="search"]',
      'input[name*="search" i]',
      'input[placeholder*="search" i]',
      'input[aria-label*="search" i]',
      '[role="searchbox"]',
    ];

    const searchInputs = searchSelectors
      .flatMap((selector) => Array.from(document.querySelectorAll(selector)))
      .filter(isVisible);

    if (searchInputs.length > 0) {
      return {
        flow_type: "basic_search",
        reason: "Search input detected on the page.",
        confidence: 0.95,
      };
    }

    const ctaCandidates = Array.from(
      document.querySelectorAll(
        'button, a[role="button"], input[type="submit"], a, [aria-label]',
      ),
    )
      .filter(isVisible)
      .map((element) => {
        const box = element.getBoundingClientRect();
        const text = (
          element.innerText ||
          element.value ||
          element.getAttribute("aria-label") ||
          ""
        )
          .trim()
          .toLowerCase();

        let score = 0;

        if (ctaKeywords.some((keyword) => text.includes(keyword))) score += 60;
        if (element.tagName.toLowerCase() === "button") score += 20;
        if (element.getAttribute("role") === "button") score += 15;
        if (element.tagName.toLowerCase() === "input") score += 15;
        if (box.top >= 0 && box.top <= window.innerHeight) score += 15;
        if (box.width >= 80 && box.height >= 30) score += 10;

        if (
          element.disabled ||
          element.getAttribute("aria-disabled") === "true"
        ) {
          score -= 100;
        }

        return {
          text,
          score,
        };
      })
      .filter((item) => item.score > 0)
      .sort((a, b) => b.score - a.score);

    if (ctaCandidates.length > 0) {
      return {
        flow_type: "cta_click",
        reason: "CTA/button candidate detected on the page.",
        confidence: Math.min(0.9, ctaCandidates[0].score / 100),
      };
    }

    const forms = Array.from(document.querySelectorAll("form")).filter(
      isVisible,
    );

    if (forms.length > 0) {
      return {
        flow_type: "cta_click",
        reason: "Form detected. Using CTA click flow as fallback.",
        confidence: 0.7,
      };
    }

    return {
      flow_type: "landing_navigation",
      reason:
        "No search input or strong CTA detected. Using landing navigation.",
      confidence: 0.6,
    };
  }, CTA_KEYWORDS);
}

module.exports = {
  VIEWPORTS,
  NETWORK_PROFILES,
  createEmptyMetrics,
  setupBrowserMetricListeners,
  getNavigationTiming,
  getBrowserMetrics,
  detectPageSignals,
  detectBestFlow,
  scrollPage,
  clickBestCTA,
  runBasicSearch,
  drawGAgentAnnotations,
  clearGAgentAnnotations,
  mergeMetrics,
  round,
};
