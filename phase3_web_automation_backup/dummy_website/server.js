const express = require("express");
const app = express();

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static("public"));

const PORT = 3000;

const frictionMap = {
  good: "Low",
  medium: "Medium",
  bad: "High",
};

const clarityMap = {
  good: 2,
  medium: 1,
  bad: 0,
  none: -1,
};

function delayExpression(level) {
  const base = level === "good" ? 350 : level === "medium" ? 1350 : 2800;
  const jitter = level === "good" ? 180 : level === "medium" ? 550 : 950;
  return `${base} + Math.floor(Math.random() * ${jitter})`;
}

function titleCase(text) {
  return text.charAt(0).toUpperCase() + text.slice(1);
}

function layout(title, body, meta = {}) {
  const flow = meta.flow || "dashboard";
  const scenario = meta.scenario || "none";
  const siteStyle = meta.siteStyle || "main";
  const frictionLevel = meta.frictionLevel || frictionMap[scenario] || "None";
  const errorMessageClarity = Number.isInteger(meta.errorMessageClarity)
    ? meta.errorMessageClarity
    : (clarityMap[scenario] ?? -1);

  return `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/phase35-style.css">
</head>
<body
  class="phase35-body style-${siteStyle} ${scenario}-friction"
  data-site-style="${siteStyle}"
  data-flow="${flow}"
  data-scenario="${scenario}"
  data-friction-level="${frictionLevel}"
  data-error-message-clarity="${errorMessageClarity}"
>
  <main class="container phase35-page">
    ${body}
  </main>
</body>
</html>
`;
}

function scenarioHeader(flow, level, description, siteStyle = "main") {
  return `
<section class="scenario-banner phase35-hero ${level}-hero ${level}-banner">
  <p class="eyebrow">GAGENT ${siteStyle.toUpperCase()} SCENARIO</p>
  <h1>${titleCase(siteStyle)} ${titleCase(flow)} Page - ${level.toUpperCase()}</h1>
  <p><strong>Expected friction_level:</strong> ${frictionMap[level]}</p>
  <p>${description}</p>
</section>
`;
}

function topNav() {
  return `
<nav class="top-nav phase35-nav">
  <a href="/">← Main Dashboard</a>
</nav>
`;
}

function popupHtml(type) {
  if (type === "cookie") {
    return `
<div id="cookiePopup" class="popup-banner" data-popup="cookie">
  <p>This site uses cookies for testing. The agent should close this before continuing.</p>
  <button id="acceptCookies" aria-label="Accept cookies" onclick="document.getElementById('cookiePopup').remove()">Accept Cookies</button>
</div>
`;
  }

  if (type === "newsletter") {
    return `
<div id="newsletterModal" class="modal-backdrop" data-popup="newsletter">
  <div class="modal-card">
    <h2>Join our newsletter</h2>
    <p>This popup simulates a marketing modal that blocks the main action.</p>
    <button id="closeNewsletter" aria-label="Close newsletter popup" onclick="document.getElementById('newsletterModal').remove()">Close</button>
  </div>
</div>
`;
  }

  return "";
}

function loadingOverlayHtml(level) {
  if (level !== "bad") return "";

  return `
<div id="loadingOverlay" class="blocking-overlay" data-popup="loading-overlay">
  <div class="modal-card">
    <p class="spinner-text">Loading verification layer...</p>
    <button id="closeOverlay" class="secondary-button" onclick="document.getElementById('loadingOverlay').remove()">Dismiss overlay</button>
  </div>
</div>
`;
}

function messageScript({
  formId,
  buttonId,
  successText,
  delay,
  validationCondition,
  validationMessage,
  clarity,
  resultText = "",
}) {
  return `
<script>
  const form = document.getElementById("${formId}");
  const actionButton = document.getElementById("${buttonId}");
  const message = document.getElementById("message");

  function showError() {
    message.className = "validation-message ${clarity === 2 ? "clear-box" : clarity === 1 ? "partial-box" : "vague-box"}";
    message.setAttribute("data-status", "error");
    message.setAttribute("data-error-message-clarity", "${clarity}");
    message.innerText = "${validationMessage}";
  }

  function showSuccess() {
    message.className = "validation-message loading-box";
    message.setAttribute("data-status", "loading");
    message.innerText = "Processing...";

    setTimeout(() => {
      message.className = "validation-message success-box";
      message.setAttribute("data-status", "success");
      message.innerText = "${successText}";

      const result = document.getElementById("result") || document.getElementById("results");
      if (result) {
        result.innerText = "${resultText}";
        result.setAttribute("data-status", "success");
      }
    }, ${delay});
  }

  function handleAction(event) {
    if (event) event.preventDefault();

    if (${validationCondition}) {
      showError();
      return;
    }

    showSuccess();
  }

  if (form) {
    form.addEventListener("submit", handleAction);
  }

  if (actionButton && !form) {
    actionButton.addEventListener("click", handleAction);
  }
</script>
`;
}

/* ============================================================
   ORIGINAL PHASE 3 ROUTES
   /signup-good, /login-good, /search-good, /landing-good, /cta-good
============================================================ */

function signupPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Clear labels, direct button, short feedback delay."
      : level === "medium"
        ? "Extra input fields, partially clear guidance, and slower feedback."
        : "Fake button, vague validation, hidden real submit button, and long feedback delay.";

  const fakeButton =
    level === "bad"
      ? `<button id="fakeSignupBtn" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Create Account</button>`
      : "";

  const extraFields =
    level === "good"
      ? ""
      : `
<label>Referral Code
  <input id="referral" name="referral" placeholder="${level === "medium" ? "Optional code" : "Put thing here"}">
</label>

<label class="checkbox-row">
  <input id="terms" name="terms" type="checkbox">
  <span>${level === "medium" ? "I accept the account terms" : "Confirm unknown required option"}</span>
</label>
`;

  const spacing =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">Some action might be below.</p></div>`
      : "";

  const validation =
    level === "good"
      ? "!document.getElementById('name').value || !document.getElementById('email').value || !document.getElementById('password').value"
      : "!document.getElementById('name').value || !document.getElementById('email').value || !document.getElementById('password').value || !document.getElementById('terms').checked";

  const validationMessage =
    level === "good"
      ? "Please enter your full name, email address, and password."
      : level === "medium"
        ? "Some registration details are incomplete. Check the form again."
        : "Problem. Fix it.";

  return layout(
    `Signup ${level}`,
    `
${topNav()}
${scenarioHeader("signup", level, description, "main")}

<form id="signupForm" class="card-panel" novalidate>
  ${fakeButton}

  <label>Full Name
    <input id="name" name="name" placeholder="${level === "bad" ? "Identity text" : "Enter your full name"}">
  </label>

  <label>Email Address
    <input id="email" name="email" type="email" placeholder="${level === "bad" ? "Contact value" : "Enter your email"}">
  </label>

  <label>Password
    <input id="password" name="password" type="password" placeholder="${level === "bad" ? "Secret value" : "Enter password"}">
  </label>

  ${extraFields}
  ${spacing}

  <button id="submitBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="submit">
    ${level === "good" ? "Create Account" : level === "medium" ? "Continue Registration" : "Continue Maybe"}
  </button>
</form>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>

${messageScript({
  formId: "signupForm",
  buttonId: "submitBtn",
  successText: "Signup completed successfully",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
})}
`,
    {
      flow: "signup",
      scenario: level,
      siteStyle: "main",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function loginPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Clear login labels and direct success feedback."
      : level === "medium"
        ? "A wrong first attempt creates a partially clear validation error before retry."
        : "A fake button and vague error create failed clicks, retries, and extra delay.";

  const fakeButton =
    level === "bad"
      ? `<button id="fakeLoginBtn" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Login</button>`
      : "";

  const spacing =
    level === "bad" ? `<div class="hidden-cta-space small-space"></div>` : "";

  const validation =
    "document.getElementById('email').value.trim() !== 'user@example.com' || document.getElementById('password').value.trim() !== 'Password123!'";

  const validationMessage =
    level === "good"
      ? "Email or password is incorrect. Use user@example.com and the correct password."
      : level === "medium"
        ? "The login details do not match. Check one of the fields."
        : "No. Try again.";

  return layout(
    `Login ${level}`,
    `
${topNav()}
${scenarioHeader("login", level, description, "main")}

<form id="loginForm" class="card-panel" novalidate>
  ${fakeButton}

  <label>${level === "bad" ? "User identifier" : "Email Address"}
    <input id="email" name="email" type="email" placeholder="user@example.com">
  </label>

  <label>${level === "bad" ? "Access key" : "Password"}
    <input id="password" name="password" type="password" placeholder="Password123!">
  </label>

  ${spacing}

  <button id="loginBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="submit">
    ${level === "bad" ? "Continue Maybe" : "Login"}
  </button>
</form>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>

${messageScript({
  formId: "loginForm",
  buttonId: "loginBtn",
  successText: "Login successful",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
})}
`,
    {
      flow: "login",
      scenario: level,
      siteStyle: "main",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function searchPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Direct search interaction with short delay."
      : level === "medium"
        ? "Unclear wording and disabled control create failed click and retry metrics."
        : "Fake control, vague error, scrolling, and long delay create high friction.";

  const fakeButton =
    level === "medium"
      ? `<button id="clearSearchFake" type="button" class="disabled-looking" disabled>Clear Search</button>`
      : level === "bad"
        ? `<button id="fakeSearchBtn" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Search</button>`
        : "";

  const spacing =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">The useful action is not near the search box.</p></div>`
      : "";

  const validation = "!document.getElementById('searchInput').value.trim()";

  const validationMessage =
    level === "good"
      ? "Enter a search keyword before pressing Search."
      : level === "medium"
        ? "Search cannot continue because something is missing."
        : "Missing.";

  return layout(
    `Search ${level}`,
    `
${topNav()}
${scenarioHeader("search", level, description, "main")}

<section class="card-panel">
  <label>${level === "bad" ? "Query value" : "Search keyword"}
    <input id="searchInput" name="q" type="search" role="searchbox" placeholder="${level === "bad" ? "Type something somewhere" : "Search products"}">
  </label>

  ${fakeButton}
  ${spacing}

  <button id="searchBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
    ${level === "good" ? "Search" : level === "medium" ? "Find" : "Go Maybe"}
  </button>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>
<div id="results" class="results-box"></div>

${messageScript({
  formId: "none",
  buttonId: "searchBtn",
  successText: "Search completed",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
  resultText: "3 results found",
})}
`,
    {
      flow: "search",
      scenario: level,
      siteStyle: "main",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function landingPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Clear value explanation and visible CTA."
      : level === "medium"
        ? "Extra reading and optional input increase interaction cost."
        : "A fake top CTA and hidden real CTA create failed click, scroll, and retry metrics.";

  const fakeButton =
    level === "bad"
      ? `<button id="fakeLandingCta" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Start Now</button>`
      : "";

  const interestInput =
    level === "medium"
      ? `<label>Optional goal<input id="interestInput" placeholder="Type your goal before starting"></label>`
      : "";

  const spacing =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">The main action is hidden below a long section and the top action is fake.</p></div>`
      : "";

  return layout(
    `Landing ${level}`,
    `
${topNav()}
${scenarioHeader("landing", level, description, "main")}

<section class="card-panel">
  <p>${level === "good" ? "Clear product explanation." : "Longer and less direct explanation that may distract the user."}</p>

  ${fakeButton}
  ${interestInput}
  ${spacing}

  <button id="mainCta" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
    ${level === "medium" ? "Explore and Start" : "Start Now"}
  </button>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${level === "good" ? -1 : clarityMap[level]}"></div>

${messageScript({
  formId: "none",
  buttonId: "mainCta",
  successText: "Landing task completed",
  delay,
  validationCondition: "false",
  validationMessage: "",
  clarity: level === "good" ? -1 : clarityMap[level],
})}
`,
    {
      flow: "landing",
      scenario: level,
      siteStyle: "main",
      frictionLevel: frictionMap[level],
      errorMessageClarity: level === "good" ? -1 : clarityMap[level],
    },
  );
}

function ctaPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Direct call-to-action with clear wording."
      : level === "medium"
        ? "Disabled-looking Buy Now control and indirect real CTA create a retry."
        : "Fake top CTA, hidden real CTA, vague wording, and slow feedback create high friction.";

  const fakeButton =
    level === "medium"
      ? `<button id="fakeBuyBtn" type="button" class="disabled-looking" disabled>Buy Now</button>`
      : level === "bad"
        ? `<button id="fakeCtaBtn" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Buy Now</button>`
        : "";

  const spacing =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">The actual action is below this empty space.</p></div>`
      : level === "medium"
        ? `<p class="partial-box">The real CTA uses indirect wording.</p>`
        : "";

  return layout(
    `CTA ${level}`,
    `
${topNav()}
${scenarioHeader("cta", level, description, "main")}

<section class="card-panel">
  <p>Find and click the correct call-to-action button.</p>

  ${fakeButton}
  ${spacing}

  <button id="ctaBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
    ${level === "good" ? "Buy Now" : level === "medium" ? "Proceed" : "Maybe Continue"}
  </button>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${level === "good" ? -1 : clarityMap[level]}"></div>

${messageScript({
  formId: "none",
  buttonId: "ctaBtn",
  successText: "CTA task completed",
  delay,
  validationCondition: "false",
  validationMessage: "",
  clarity: level === "good" ? -1 : clarityMap[level],
})}
`,
    {
      flow: "cta",
      scenario: level,
      siteStyle: "main",
      frictionLevel: frictionMap[level],
      errorMessageClarity: level === "good" ? -1 : clarityMap[level],
    },
  );
}

/* ============================================================
   PHASE 3.5 ROUTES
   /saas, /ecommerce, /banking, /jobportal, /dashboard
============================================================ */

function saasSignupPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Modern SaaS signup with semantic labels and clear Create Account action."
      : level === "medium"
        ? "SaaS signup with cookie popup, extra company field, and partial validation guidance."
        : "SaaS signup with newsletter modal, vague field labels, fake Get Started button, and hidden real action.";

  const fake =
    level === "bad"
      ? `<button id="fakeSaasSignup" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Get Started</button>`
      : "";

  const extra =
    level !== "good"
      ? `
<label>Company ${level === "bad" ? "Thing" : "Name"}
  <input id="company" name="company" placeholder="${level === "bad" ? "Put business stuff" : "Company name"}">
</label>

<label class="checkbox-row">
  <input id="terms" type="checkbox">
  <span>${level === "bad" ? "Confirm unknown platform option" : "Accept workspace terms"}</span>
</label>
`
      : "";

  const hiddenSpace =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">The real SaaS action is lower on the page.</p></div>`
      : "";

  const validation =
    level === "good"
      ? "!document.getElementById('email').value || !document.getElementById('password').value"
      : "!document.getElementById('email').value || !document.getElementById('password').value || !document.getElementById('company').value || !document.getElementById('terms').checked";

  const validationMessage =
    level === "good"
      ? "Enter email and password to create your workspace."
      : level === "medium"
        ? "Some workspace details are incomplete. Check required fields."
        : "Something is missing.";

  return layout(
    `SaaS Signup ${level}`,
    `
${level === "medium" ? popupHtml("cookie") : ""}
${level === "bad" ? popupHtml("newsletter") : ""}
${topNav()}
${scenarioHeader("signup", level, description, "saas")}

<section class="card-panel saas-panel">
  ${fake}

  <form id="saasSignupForm" novalidate>
    <label>${level === "bad" ? "Contact" : "Work Email"}
      <input id="email" name="email" type="email" placeholder="${level === "bad" ? "Your contact thing" : "you@company.com"}" aria-label="${level === "bad" ? "Contact" : "Work email"}">
    </label>

    <label>${level === "bad" ? "Secret" : "Password"}
      <input id="password" name="password" type="password" placeholder="Create password" aria-label="Password">
    </label>

    ${extra}
    ${hiddenSpace}

    <button id="saasSubmit" type="submit" class="${level === "bad" ? "hidden-button" : "primary-button"}">
      ${level === "good" ? "Create Account" : level === "medium" ? "Join Now" : "Continue"}
    </button>
  </form>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>

${messageScript({
  formId: "saasSignupForm",
  buttonId: "saasSubmit",
  successText: "SaaS signup completed",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
})}
`,
    {
      flow: "signup",
      scenario: level,
      siteStyle: "saas",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function ecommerceSearchPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "E-commerce search with obvious searchbox and Search button."
      : level === "medium"
        ? "E-commerce search with cookie popup, filter panel, and indirect Find Items button."
        : "E-commerce search with fake visible control, vague empty-query error, and hidden real button.";

  const fake =
    level === "bad"
      ? `<button id="fakeProductSearch" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Search</button>`
      : "";

  const filter =
    level !== "good"
      ? `
<label>Category Filter
  <select id="category">
    <option value="">Choose category</option>
    <option>Electronics</option>
  </select>
</label>
`
      : `<p class="clear-box">No filter required.</p>`;

  const hiddenSpace =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">Search controls are separated from the result area.</p></div>`
      : "";

  const validation =
    level === "good"
      ? "!document.getElementById('productQuery').value"
      : "!document.getElementById('productQuery').value || !document.getElementById('category').value";

  const validationMessage =
    level === "good"
      ? "Enter a product keyword before searching."
      : level === "medium"
        ? "Select a category or enter a keyword to continue."
        : "Cannot do it.";

  return layout(
    `E-commerce Search ${level}`,
    `
${level === "medium" ? popupHtml("cookie") : ""}
${level === "bad" ? loadingOverlayHtml(level) : ""}
${topNav()}
${scenarioHeader("search", level, description, "ecommerce")}

<section class="store-layout">
  <aside class="filter-panel">${filter}</aside>

  <div class="catalog-panel">
    <label>${level === "bad" ? "Product thing" : "Search products"}
      <input id="productQuery" name="q" type="search" role="searchbox" placeholder="${level === "bad" ? "Type maybe" : "Search products"}">
    </label>

    ${fake}
    ${hiddenSpace}

    <button id="productSearchBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
      ${level === "good" ? "Search" : level === "medium" ? "Find Items" : "Go"}
    </button>
  </div>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>
<div id="result" class="results-box"></div>

${messageScript({
  formId: "none",
  buttonId: "productSearchBtn",
  successText: "Product search completed",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
  resultText: "4 products found",
})}
`,
    {
      flow: "search",
      scenario: level,
      siteStyle: "ecommerce",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function bankingLoginPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Banking-style login with clear User ID and Access Account action."
      : level === "medium"
        ? "Banking login with security question and partial error guidance."
        : "Banking login with unsupported security block style and vague error wording. CAPTCHA is not bypassed.";

  const securityBlock =
    level === "bad"
      ? `
<div id="captchaBox" class="security-box" data-security="captcha">
  <strong>Security Verification</strong>
  <p>CAPTCHA/security check detected. Automation must not bypass this.</p>
</div>
`
      : "";

  const extra =
    level !== "good"
      ? `
<label>Security Answer
  <input id="securityAnswer" placeholder="${level === "bad" ? "Answer unknown thing" : "Mother's maiden name"}">
</label>
`
      : "";

  const fake =
    level === "bad"
      ? `<button id="fakeBankLogin" type="button" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Sign In</button>`
      : "";

  const validation =
    level === "good"
      ? "document.getElementById('userId').value !== 'user123' || document.getElementById('pin').value !== '123456'"
      : "document.getElementById('userId').value !== 'user123' || document.getElementById('pin').value !== '123456' || !document.getElementById('securityAnswer').value";

  const validationMessage =
    level === "good"
      ? "User ID or PIN is incorrect."
      : level === "medium"
        ? "Some login information is not accepted. Check the form."
        : "Blocked.";

  return layout(
    `Banking Login ${level}`,
    `
${level === "medium" ? popupHtml("cookie") : ""}
${topNav()}
${scenarioHeader("login", level, description, "banking")}

<section class="bank-card">
  ${securityBlock}
  ${fake}

  <form id="bankLoginForm" novalidate>
    <label>${level === "bad" ? "Identifier" : "User ID"}
      <input id="userId" autocomplete="username" placeholder="user123">
    </label>

    <label>${level === "bad" ? "Access number" : "PIN"}
      <input id="pin" type="password" inputmode="numeric" placeholder="123456">
    </label>

    ${extra}

    <button id="bankLoginBtn" type="submit" class="${level === "bad" ? "hidden-button" : "primary-button"}">
      ${level === "good" ? "Access Account" : level === "medium" ? "Continue" : "Verify"}
    </button>
  </form>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>

${messageScript({
  formId: "bankLoginForm",
  buttonId: "bankLoginBtn",
  successText: "Banking login successful",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
})}
`,
    {
      flow: "login",
      scenario: level,
      siteStyle: "banking",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function jobportalSearchPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Job portal search with clear role keyword field and search button."
      : level === "medium"
        ? "Job search with location field, modal popup, and partial validation."
        : "Job search with fake search action, hidden real button, vague error, and extra scroll.";

  const extra =
    level !== "good"
      ? `
<label>Location
  <input id="location" placeholder="${level === "bad" ? "Area maybe" : "Kuala Lumpur"}">
</label>
`
      : "";

  const fake =
    level === "bad"
      ? `<button id="fakeJobSearch" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Search Jobs</button>`
      : "";

  const hiddenSpace =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">The real job search action is below sponsored content.</p></div>`
      : "";

  const validation =
    level === "good"
      ? "!document.getElementById('keyword').value"
      : "!document.getElementById('keyword').value || !document.getElementById('location').value";

  const validationMessage =
    level === "good"
      ? "Enter a job keyword before searching."
      : level === "medium"
        ? "Keyword or location may be incomplete."
        : "No result.";

  return layout(
    `Job Portal Search ${level}`,
    `
${level === "medium" ? popupHtml("newsletter") : ""}
${topNav()}
${scenarioHeader("search", level, description, "jobportal")}

<section class="job-search-panel">
  ${fake}

  <label>${level === "bad" ? "Work thing" : "Job Keyword"}
    <input id="keyword" name="q" placeholder="${level === "bad" ? "Type job maybe" : "Data analyst"}" aria-label="Job keyword">
  </label>

  ${extra}
  ${hiddenSpace}

  <button id="jobSearchBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
    ${level === "good" ? "Search Jobs" : level === "medium" ? "Find Roles" : "Continue"}
  </button>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${clarityMap[level]}"></div>
<div id="result" class="results-box"></div>

${messageScript({
  formId: "none",
  buttonId: "jobSearchBtn",
  successText: "Job search completed",
  delay,
  validationCondition: validation,
  validationMessage,
  clarity: clarityMap[level],
  resultText: "6 jobs found",
})}
`,
    {
      flow: "search",
      scenario: level,
      siteStyle: "jobportal",
      frictionLevel: frictionMap[level],
      errorMessageClarity: clarityMap[level],
    },
  );
}

function dashboardCtaPage(level) {
  const delay = delayExpression(level);

  const description =
    level === "good"
      ? "Dashboard app with visible Continue button."
      : level === "medium"
        ? "Dashboard with loading panel, secondary navigation, and indirect Proceed button."
        : "Dashboard with hidden CTA behind dense widgets, fake action button, and long feedback delay.";

  const fake =
    level === "bad"
      ? `<button id="fakeDashboardCta" class="fake-button" onclick="document.getElementById('message').setAttribute('data-status','fake-click')">Continue</button>`
      : "";

  const widgets = Array.from(
    { length: level === "bad" ? 8 : 3 },
    (_, i) => `
<article class="widget-card">
  <h3>Widget ${i + 1}</h3>
  <p>Dashboard summary content.</p>
</article>
`,
  ).join("");

  const hiddenSpace =
    level === "bad"
      ? `<div class="hidden-cta-space"><p class="vague-box">Main workflow action appears after dense dashboard content.</p></div>`
      : "";

  return layout(
    `Dashboard CTA ${level}`,
    `
${level === "medium" ? popupHtml("cookie") : ""}
${level === "bad" ? loadingOverlayHtml(level) : ""}
${topNav()}
${scenarioHeader("cta", level, description, "dashboard")}

<section class="dashboard-app-grid">
  ${fake}
  ${widgets}
  ${hiddenSpace}

  <button id="dashboardCtaBtn" class="${level === "bad" ? "hidden-button" : "primary-button"}" type="button">
    ${level === "good" ? "Continue" : level === "medium" ? "Proceed" : "Next"}
  </button>
</section>

<div id="message" class="validation-message" data-error-message-clarity="${level === "good" ? -1 : clarityMap[level]}"></div>

${messageScript({
  formId: "none",
  buttonId: "dashboardCtaBtn",
  successText: "Dashboard CTA completed",
  delay,
  validationCondition: "false",
  validationMessage: "",
  clarity: level === "good" ? -1 : clarityMap[level],
})}
`,
    {
      flow: "cta",
      scenario: level,
      siteStyle: "dashboard",
      frictionLevel: frictionMap[level],
      errorMessageClarity: level === "good" ? -1 : clarityMap[level],
    },
  );
}

/* ============================================================
   INDEX ROUTES
============================================================ */

app.get("/", (req, res) => {
  const originalFlows = [
    {
      title: "Landing Scenarios",
      flow: "landing",
      base: "/landing",
      description: "Original landing page UX friction scenarios.",
    },
    {
      title: "Signup Scenarios",
      flow: "signup",
      base: "/signup",
      description: "Original signup form UX friction scenarios.",
    },
    {
      title: "Login Scenarios",
      flow: "login",
      base: "/login",
      description: "Original login UX friction scenarios.",
    },
    {
      title: "Search Scenarios",
      flow: "search",
      base: "/search",
      description: "Original search UX friction scenarios.",
    },
    {
      title: "CTA Scenarios",
      flow: "cta",
      base: "/cta",
      description: "Original call-to-action UX friction scenarios.",
    },
  ];

  const styleFlows = [
    {
      title: "SaaS Signup",
      flow: "signup",
      base: "/saas/signup",
      description:
        "SaaS-style signup with cookie popup, modal, and varied CTA labels.",
    },
    {
      title: "E-commerce Search",
      flow: "search",
      base: "/ecommerce/search",
      description:
        "E-commerce-style product search with filters and fake search controls.",
    },
    {
      title: "Banking Login",
      flow: "login",
      base: "/banking/login",
      description:
        "Banking/form-style login with security question and CAPTCHA block simulation.",
    },
    {
      title: "Job Portal Search",
      flow: "search",
      base: "/jobportal/search",
      description:
        "Job portal search with keyword, location, modal, and hidden action variation.",
    },
    {
      title: "Dashboard CTA",
      flow: "cta",
      base: "/dashboard/cta",
      description:
        "Dashboard app CTA flow with dense widgets, overlays, and hidden buttons.",
    },
  ];

  function makeCards(items) {
    return items
      .map(
        (item) => `
<article class="dashboard-card">
  <h2>${item.title}</h2>
  <p>${item.description}</p>
  <ul>
    <li><a href="${item.base}-good">${item.flow} good - Low friction</a></li>
    <li><a href="${item.base}-medium">${item.flow} medium - Medium friction</a></li>
    <li><a href="${item.base}-bad">${item.flow} bad - High friction</a></li>
  </ul>
</article>
`,
      )
      .join("");
  }

  res.send(
    layout(
      "GAgent Dummy Website",
      `
${topNav()}

<section class="scenario-banner good-banner phase35-hero good-hero">
  <p class="eyebrow">CONTROLLED UX TEST SITE</p>
  <h1>GAgent Dummy Website</h1>
  <p>This single dashboard contains both the original Phase 3 scenarios and the Phase 3.5 real website style robustness scenarios.</p>
</section>

<section class="scenario-banner medium-banner phase35-hero medium-hero">
  <p class="eyebrow">PHASE 3</p>
  <h1>Original Controlled UX Friction Scenarios</h1>
  <p>These routes are used for the original Playwright-generated UX friction dataset.</p>
</section>

<section class="dashboard-grid">
  ${makeCards(originalFlows)}
</section>

<section class="scenario-banner bad-banner phase35-hero bad-hero">
  <p class="eyebrow">PHASE 3.5</p>
  <h1>Real Website Style Robustness Scenarios</h1>
  <p>These routes simulate different website styles such as SaaS, e-commerce, banking, job portal, and dashboard applications.</p>
</section>

<section class="dashboard-grid">
  ${makeCards(styleFlows)}
</section>
`,
      {
        flow: "dashboard",
        scenario: "none",
        siteStyle: "main",
        errorMessageClarity: -1,
      },
    ),
  );
});

/* ============================================================
   ROUTE REGISTRATION
============================================================ */

["good", "medium", "bad"].forEach((level) => {
  app.get(`/signup-${level}`, (req, res) => res.send(signupPage(level)));
  app.get(`/login-${level}`, (req, res) => res.send(loginPage(level)));
  app.get(`/search-${level}`, (req, res) => res.send(searchPage(level)));
  app.get(`/landing-${level}`, (req, res) => res.send(landingPage(level)));
  app.get(`/cta-${level}`, (req, res) => res.send(ctaPage(level)));

  app.get(`/saas/signup-${level}`, (req, res) =>
    res.send(saasSignupPage(level)),
  );
  app.get(`/ecommerce/search-${level}`, (req, res) =>
    res.send(ecommerceSearchPage(level)),
  );
  app.get(`/banking/login-${level}`, (req, res) =>
    res.send(bankingLoginPage(level)),
  );
  app.get(`/jobportal/search-${level}`, (req, res) =>
    res.send(jobportalSearchPage(level)),
  );
  app.get(`/dashboard/cta-${level}`, (req, res) =>
    res.send(dashboardCtaPage(level)),
  );
});

app.listen(PORT, () => {
  console.log(`Dummy website running at http://localhost:${PORT}`);
  console.log(`Main page: http://localhost:${PORT}`);
  console.log(`Phase 3.5 page: http://localhost:${PORT}/phase35`);
});
