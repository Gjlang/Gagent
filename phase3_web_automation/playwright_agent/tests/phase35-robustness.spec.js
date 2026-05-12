const { test } = require("@playwright/test");
const { runRobustScenario } = require("../utils/robust-agent-helper");

const BASE_URL = process.env.GAGENT_BASE_URL || "http://localhost:3000";

const DEFAULT_VIEWPORTS = (
  process.env.GAGENT_VIEWPORTS || "mobile,tablet,laptop,desktop"
)
  .split(",")
  .map((value) => value.trim())
  .filter(Boolean);

const scenarios = [
  {
    siteStyle: "saas",
    flowType: "signup",
    routeBase: "/saas/signup",
  },
  {
    siteStyle: "ecommerce",
    flowType: "search",
    routeBase: "/ecommerce/search",
  },
  {
    siteStyle: "banking",
    flowType: "login",
    routeBase: "/banking/login",
  },
  {
    siteStyle: "jobportal",
    flowType: "search",
    routeBase: "/jobportal/search",
  },
  {
    siteStyle: "dashboard",
    flowType: "cta",
    routeBase: "/dashboard/cta",
  },
];

const frictionScenarios = ["good", "medium", "bad"];

for (const scenario of scenarios) {
  for (const frictionScenario of frictionScenarios) {
    for (const viewportType of DEFAULT_VIEWPORTS) {
      test(`Phase 3.5 ${scenario.siteStyle} ${scenario.flowType} ${frictionScenario} ${viewportType}`, async ({
        page,
      }) => {
        await runRobustScenario(page, {
          siteStyle: scenario.siteStyle,
          flowType: scenario.flowType,
          scenario: frictionScenario,
          viewportType,
          pageUrl: `${BASE_URL}${scenario.routeBase}-${frictionScenario}`,
        });
      });
    }
  }
}
