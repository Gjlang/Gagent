const express = require("express");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = Number(process.env.PORT || 3000);
const SCENARIO_PATH = path.join(__dirname, "../playwright_agent/config/phase3-scenarios.json");

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

function readScenarios() {
  return JSON.parse(fs.readFileSync(SCENARIO_PATH, "utf8"));
}

const SCENARIOS = readScenarios();
const SCENARIO_MAP = new Map(SCENARIOS.map((item) => [item.route, item]));

function hashString(text) {
  let hash = 2166136261;
  for (let i = 0; i < text.length; i += 1) {
    hash ^= text.charCodeAt(i);
    hash = Math.imul(hash, 16777619);
  }
  return hash >>> 0;
}

function seededRandom(seedText) {
  let state = hashString(seedText || "gagent");
  return function random() {
    state += 0x6d2b79f5;
    let t = state;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

function intBetween(random, min, max) {
  return Math.floor(min + random() * (max - min + 1));
}

function levelFromExpected(expected) {
  if (expected === "Low") return "good";
  if (expected === "Medium") return "medium";
  return "bad";
}

function clarityForScenario(scenarioType, level) {
  if (["normal_landing", "normal_cta"].includes(scenarioType)) return -1;
  if (scenarioType.includes("vague") || scenarioType.includes("hidden_submit")) return 0;
  if (level === "good") return 2;
  if (level === "medium") return 1;
  return 0;
}

function computeVariation(scenario, req) {
  const seed = String(req.query.seed || `${scenario.route}-${Date.now()}`);
  const network = String(req.query.network || "normal");
  const random = seededRandom(`${scenario.route}|${seed}|${network}`);
  const scenarioType = scenario.scenario_type;
  const level = levelFromExpected(scenario.expected_friction_level);

  let pageDelayMin = level === "good" ? 20 : level === "medium" ? 250 : 600;
  let pageDelayMax = level === "good" ? 120 : level === "medium" ? 900 : 1600;
  let feedbackMin = level === "good" ? 120 : level === "medium" ? 700 : 1800;
  let feedbackMax = level === "good" ? 450 : level === "medium" ? 2400 : 5200;

  if (scenarioType === "slow_page_load") {
    pageDelayMin = 500;
    pageDelayMax = 6000;
  }
  if (scenarioType === "slow_button_response") {
    feedbackMin = 1200;
    feedbackMax = 6000;
  }
  if (scenarioType === "timeout") {
    feedbackMin = 8000;
    feedbackMax = 12000;
  }
  if (network === "slow") {
    pageDelayMin += 500;
    pageDelayMax += 1500;
    feedbackMin += 350;
    feedbackMax += 1200;
  } else if (network === "fast") {
    pageDelayMax = Math.max(pageDelayMin + 50, Math.floor(pageDelayMax * 0.65));
    feedbackMax = Math.max(feedbackMin + 80, Math.floor(feedbackMax * 0.7));
  }

  return {
    seed,
    network,
    pageDelayMs: intBetween(random, pageDelayMin, pageDelayMax),
    feedbackDelayMs: intBetween(random, feedbackMin, feedbackMax),
    popupDelayMs: intBetween(random, 300, 1800),
    layoutShiftDelayMs: intBetween(random, 350, 1700),
    layoutShiftHeight: intBetween(random, 80, 260),
    ctaShiftX: intBetween(random, 18, 140),
    overlayMode: random() > 0.5 ? "full" : "partial",
    smallButtonSize: intBetween(random, 16, 28),
    level,
    clarity: clarityForScenario(scenarioType, level),
  };
}

function esc(value) {
  return String(value).replace(/[&<>'"]/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "'": "&#39;",
    "\"": "&quot;",
  }[char]));
}

function htmlPage(title, scenario, variation, body) {
  return `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${esc(title)}</title>
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/phase3-controlled.css">
</head>
<body
  data-flow-type="${esc(scenario.flow_type)}"
  data-scenario-type="${esc(scenario.scenario_type)}"
  data-expected-friction-level="${esc(scenario.expected_friction_level)}"
  data-error-message-clarity="${variation.clarity}"
  data-feedback-delay-ms="${variation.feedbackDelayMs}"
  data-seed="${esc(variation.seed)}"
  data-network="${esc(variation.network)}"
>
  <main class="container phase3-page ${variation.level}-page">
    <nav class="top-nav"><a href="/">GAgent Phase 3 Dummy Website</a></nav>
    <section class="scenario-banner ${variation.level}-banner">
      <p class="eyebrow">CONTROLLED UX FRICTION SCENARIO</p>
      <h1>${esc(scenario.flow_type)} / ${esc(scenario.scenario_type)}</h1>
      <p><strong>Expected label:</strong> ${esc(scenario.expected_friction_level)}</p>
      <p><strong>UX problem:</strong> ${esc(scenario.ux_problem)}</p>
      <p class="muted">Seed: ${esc(variation.seed)} | Network: ${esc(variation.network)} | Feedback delay: ${variation.feedbackDelayMs}ms</p>
    </section>
    ${body}
  </main>
  ${obstructionHtml(scenario, variation)}
  ${clientScript(scenario, variation)}
</body>
</html>`;
}

function obstructionHtml(scenario, variation) {
  const type = scenario.scenario_type;
  if (type === "cookie_banner_blocking") {
    return `<div id="cookieBanner" class="cookie-banner" data-cookie-banner="true"><p>Cookie banner blocks the lower CTA.</p><button id="acceptCookies" type="button">Accept cookies</button></div>`;
  }
  if (type === "popup_blocking") {
    return `<div id="modalOverlay" class="modal-backdrop" data-popup="true" data-overlay="true" style="animation-delay:${variation.popupDelayMs}ms"><div class="modal-card"><h2>Marketing popup</h2><p>This popup blocks the main action.</p><button id="closePopup" type="button">Close popup</button></div></div>`;
  }
  if (type === "overlay_blocks_cta" || type === "disabled_button_blocked_submit") {
    return `<div id="blockingOverlay" class="blocking-overlay ${variation.overlayMode}" data-overlay="true"><div class="modal-card"><h2>Blocked action</h2><p>The target action is blocked by an overlay.</p></div></div>`;
  }
  return "";
}

function actionButton(id, text, scenarioType, variation) {
  let className = "primary-button";
  let disabled = "";
  if (["hidden_cta", "hidden_search_button", "hidden_submit_vague_error", "hidden_cta_requires_scroll", "fake_login_button"].includes(scenarioType)) className = "primary-button hidden-button";
  if (scenarioType === "small_click_target") className = "primary-button tiny-button";
  if (scenarioType === "disabled_button_blocked_submit") disabled = "disabled aria-disabled=\"true\"";
  const style = scenarioType === "small_click_target" ? `style="width:${variation.smallButtonSize}px;height:${variation.smallButtonSize}px;padding:0;font-size:9px;"` : "";
  return `<button id="${id}" class="${className}" type="button" ${disabled} ${style}>${esc(text)}</button>`;
}

function fakeButton(id, text) {
  return `<button id="${id}" class="fake-button" type="button" data-fake-action="true">${esc(text)}</button>`;
}

function spacer(scenarioType) {
  if (["hidden_cta", "hidden_search_button", "hidden_submit_vague_error", "hidden_cta_requires_scroll", "fake_login_button"].includes(scenarioType)) {
    return `<div class="hidden-cta-space"><p>The correct action may be below this section.</p></div>`;
  }
  return "";
}

function layoutShiftBlock(scenarioType, variation) {
  if (scenarioType !== "layout_shift") return "";
  return `<div id="shiftTarget" class="shift-target" data-shift-delay="${variation.layoutShiftDelayMs}" data-shift-height="${variation.layoutShiftHeight}" data-shift-x="${variation.ctaShiftX}"></div>`;
}

function landingBody(scenario, variation) {
  const type = scenario.scenario_type;
  const fake = ["hidden_cta", "wrong_navigation_path"].includes(type) ? fakeButton("fakeLandingCta", "Start now") : "";
  const real = type === "wrong_navigation_path" ? "" : actionButton("mainCta", type === "indirect_cta_text" ? "Proceed" : "Start now", type, variation);
  return `<section class="card-panel">
    <p>Complete the landing page task by finding the correct start action.</p>
    ${layoutShiftBlock(type, variation)}
    ${fake}
    ${type === "extra_content" ? '<label>Optional goal<input id="interestInput" placeholder="Type goal"></label>' : ''}
    ${spacer(type)}
    ${real}
    <div id="message" class="validation-message" data-error-message-clarity="${variation.clarity}"></div>
  </section>`;
}

function signupBody(scenario, variation) {
  const type = scenario.scenario_type;
  const fake = ["hidden_submit_vague_error"].includes(type) ? fakeButton("fakeSignupBtn", "Create account") : "";
  return `<form id="signupForm" class="card-panel" novalidate>
    <p>Create a new account.</p>
    ${layoutShiftBlock(type, variation)}
    ${fake}
    <label>Full name<input id="name" name="name" placeholder="Enter full name"></label>
    <label>Email address<input id="email" name="email" type="email" placeholder="student@example.com"></label>
    <label>Password<input id="password" name="password" type="password" placeholder="Password123!"></label>
    ${["extra_form_fields", "vague_error_message", "hidden_submit_vague_error", "layout_shift", "disabled_button_blocked_submit"].includes(type) ? '<label>Referral code<input id="referral" name="referral" placeholder="Optional code"></label><label class="checkbox-row"><input id="terms" name="terms" type="checkbox"><span>I accept the terms</span></label>' : ''}
    ${spacer(type)}
    ${actionButton("submitBtn", type.includes("vague") ? "Continue maybe" : "Create account", type, variation)}
    <div id="message" class="validation-message" data-error-message-clarity="${variation.clarity}"></div>
  </form>`;
}

function loginBody(scenario, variation) {
  const type = scenario.scenario_type;
  const fake = ["fake_login_button"].includes(type) ? fakeButton("fakeLoginBtn", "Login") : "";
  return `<form id="loginForm" class="card-panel" novalidate>
    <p>Login using user@example.com and Password123!.</p>
    ${fake}
    <label>Email address<input id="email" name="email" type="email" placeholder="user@example.com"></label>
    <label>Password<input id="password" name="password" type="password" placeholder="Password123!"></label>
    ${spacer(type)}
    ${actionButton("loginBtn", type === "fake_login_button" ? "Continue maybe" : "Login", type, variation)}
    <div id="message" class="validation-message" data-error-message-clarity="${variation.clarity}"></div>
  </form>`;
}

function searchBody(scenario, variation) {
  const type = scenario.scenario_type;
  const fake = ["hidden_search_button"].includes(type) ? fakeButton("fakeSearchBtn", "Search") : "";
  return `<section class="card-panel">
    <p>Search for a product.</p>
    <label>Search keyword<input id="searchInput" type="search" placeholder="laptop"></label>
    ${type === "unclear_search_control" ? '<button id="fakeFilterBtn" class="disabled-looking" disabled type="button">Clear filter</button>' : ''}
    ${fake}
    ${spacer(type)}
    ${actionButton("searchBtn", type === "unclear_search_control" ? "Find" : "Search", type, variation)}
    <div id="message" class="validation-message" data-error-message-clarity="${variation.clarity}"></div>
    <div id="results" class="results-box"></div>
  </section>`;
}

function ctaBody(scenario, variation) {
  const type = scenario.scenario_type;
  const fake = ["hidden_cta", "indirect_cta_text"].includes(type) ? fakeButton("fakeCtaBtn", "Buy now") : "";
  return `<section class="card-panel cta-card">
    <p>Find the correct call-to-action.</p>
    ${fake}
    ${spacer(type)}
    ${actionButton("ctaBtn", type === "indirect_cta_text" ? "Proceed" : "Buy now", type, variation)}
    <div id="message" class="validation-message" data-error-message-clarity="${variation.clarity}"></div>
  </section>`;
}

function clientScript(scenario, variation) {
  const config = {
    flow_type: scenario.flow_type,
    scenario_type: scenario.scenario_type,
    expected_friction_level: scenario.expected_friction_level,
    feedback_delay_ms: variation.feedbackDelayMs,
    clarity: variation.clarity,
  };
  return `<script>
window.GAGENT_SCENARIO = ${JSON.stringify(config)};
(function () {
  const scenario = window.GAGENT_SCENARIO;
  const message = document.getElementById('message');
  const results = document.getElementById('results');

  function setMessage(kind, text) {
    if (!message) return;
    message.dataset.status = kind;
    message.className = 'validation-message ' + (kind === 'success' ? 'success-box' : kind === 'loading' ? 'loading-box' : scenario.clarity === 2 ? 'clear-box' : scenario.clarity === 1 ? 'partial-box' : 'vague-box');
    message.dataset.errorMessageClarity = String(scenario.clarity);
    message.textContent = text;
  }

  function validationText() {
    if (scenario.clarity === 2) return 'Please complete the highlighted required field before continuing.';
    if (scenario.clarity === 1) return 'Some details are missing. Check the form again.';
    return 'Problem. Fix it.';
  }

  function showSuccess(text) {
    setMessage('loading', 'Processing...');
    window.setTimeout(function () {
      if (scenario.scenario_type === 'timeout') return;
      if (scenario.scenario_type === 'no_result_error') {
        setMessage('error', 'No useful result was found for this task.');
        if (results) results.textContent = '0 results found';
        return;
      }
      if (scenario.scenario_type === 'overlay_blocks_cta' || scenario.scenario_type === 'disabled_button_blocked_submit') {
        setMessage('error', 'The action is blocked and cannot be completed.');
        return;
      }
      setMessage('success', text);
      if (results) results.textContent = '3 useful results found';
    }, scenario.feedback_delay_ms);
  }

  document.querySelectorAll('[data-fake-action="true"]').forEach(function (button) {
    button.addEventListener('click', function () {
      setMessage('error', 'This control does not complete the task.');
    });
  });

  const acceptCookies = document.getElementById('acceptCookies');
  if (acceptCookies) acceptCookies.addEventListener('click', function () { document.getElementById('cookieBanner')?.remove(); });
  const closePopup = document.getElementById('closePopup');
  if (closePopup) closePopup.addEventListener('click', function () { document.getElementById('modalOverlay')?.remove(); });

  const shiftTarget = document.getElementById('shiftTarget');
  if (shiftTarget) {
    window.setTimeout(function () {
      const filler = document.createElement('div');
      filler.className = 'injected-shift-content';
      filler.style.height = shiftTarget.dataset.shiftHeight + 'px';
      filler.textContent = 'Injected content caused layout movement before the user action.';
      shiftTarget.appendChild(filler);
      const cta = document.querySelector('#submitBtn, #mainCta, #ctaBtn');
      if (cta) cta.style.transform = 'translateX(' + shiftTarget.dataset.shiftX + 'px)';
    }, Number(shiftTarget.dataset.shiftDelay || 800));
  }

  const mainCta = document.getElementById('mainCta');
  if (mainCta) mainCta.addEventListener('click', function () { showSuccess('Landing task completed successfully'); });
  const ctaBtn = document.getElementById('ctaBtn');
  if (ctaBtn) ctaBtn.addEventListener('click', function () { showSuccess('CTA task completed successfully'); });
  const searchBtn = document.getElementById('searchBtn');
  if (searchBtn) searchBtn.addEventListener('click', function () {
    const input = document.getElementById('searchInput');
    if (!input || !input.value.trim()) { setMessage('error', validationText()); return; }
    showSuccess('Search completed successfully');
  });

  const signupForm = document.getElementById('signupForm');
  if (signupForm) signupForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const required = ['name', 'email', 'password'];
    const missing = required.some(function (id) { return !document.getElementById(id)?.value.trim(); });
    const terms = document.getElementById('terms');
    if (missing || (terms && !terms.checked)) { setMessage('error', validationText()); return; }
    showSuccess('Signup completed successfully');
  });

  const loginForm = document.getElementById('loginForm');
  if (loginForm) loginForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const email = document.getElementById('email')?.value.trim();
    const password = document.getElementById('password')?.value.trim();
    if (email !== 'user@example.com' || password !== 'Password123!') { setMessage('error', validationText()); return; }
    showSuccess('Login completed successfully');
  });
})();
</script>`;
}

function bodyForScenario(scenario, variation) {
  if (scenario.flow_type === "landing_navigation") return landingBody(scenario, variation);
  if (scenario.flow_type === "signup") return signupBody(scenario, variation);
  if (scenario.flow_type === "login") return loginBody(scenario, variation);
  if (scenario.flow_type === "search") return searchBody(scenario, variation);
  if (scenario.flow_type === "cta_click") return ctaBody(scenario, variation);
  return `<section class="card-panel"><p>Unknown flow.</p></section>`;
}

app.get("/", (req, res) => {
  const rows = SCENARIOS.map((scenario) => `<tr><td><a href="${scenario.route}">${scenario.route}</a></td><td>${scenario.flow_type}</td><td>${scenario.scenario_type}</td><td>${scenario.expected_friction_level}</td><td>${esc(scenario.ux_problem)}</td></tr>`).join("");
  res.send(`<!doctype html><html><head><meta charset="utf-8"><title>GAgent Phase 3 Routes</title><link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/phase3-controlled.css"></head><body><main class="container"><h1>GAgent Phase 3 Controlled Dummy Website</h1><p>This site provides controlled UX friction routes for Playwright dataset generation.</p><table><thead><tr><th>Route</th><th>Flow</th><th>Scenario</th><th>Expected</th><th>UX problem</th></tr></thead><tbody>${rows}</tbody></table></main></body></html>`);
});

app.get("/:routeName", (req, res) => {
  const route = `/${req.params.routeName}`;
  const scenario = SCENARIO_MAP.get(route);
  if (!scenario) {
    res.status(404).send("Scenario route not found");
    return;
  }
  const variation = computeVariation(scenario, req);
  const body = bodyForScenario(scenario, variation);
  windowSetTimeout(() => {
    res.send(htmlPage(route, scenario, variation, body));
  }, variation.pageDelayMs);
});

function windowSetTimeout(callback, delay) {
  setTimeout(callback, delay);
}

app.listen(PORT, () => {
  console.log(`GAgent Phase 3 dummy website running at http://localhost:${PORT}`);
  console.log(`Loaded ${SCENARIOS.length} controlled routes from ${SCENARIO_PATH}`);
});
