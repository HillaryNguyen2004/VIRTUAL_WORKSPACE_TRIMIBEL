import http from "k6/http";
import { check, sleep, group } from "k6";
import { Trend, Rate, Counter } from "k6/metrics";

// Custom metrics
const loginDuration = new Trend("login_duration", true);
const dashDuration = new Trend("dashboard_duration", true);
const projectDuration = new Trend("project_duration", true);
const adminDuration = new Trend("admin_route_duration", true);
const staffDuration = new Trend("staff_route_duration", true);
const userDuration = new Trend("user_route_duration", true);
const errorRate = new Rate("error_rate");
const totalRequests = new Counter("total_requests");

// Target
const BASE = "https://trimibel.com";

// User pool (add more accounts to match peak VU count)
const USERS = [
    { email: "user1@mail.com", password: "password", role: "admin" },
    { email: "user2@mail.com", password: "password", role: "staff" },
    { email: "user3@mail.com", password: "password", role: "staff" },
    { email: "user4@mail.com", password: "password", role: "staff" },
    { email: "user7@mail.com", password: "password", role: "user" },
    { email: "user8@mail.com", password: "password", role: "user" },
    { email: "user9@mail.com", password: "password", role: "user" },
];

// Dashboard URL per role — visited once right after login via manual GET
const DASHBOARD = {
    admin: "/admin/dashboard",
    staff: "/staff/dashboard",
    user:  "/user/dashboard",
};

// Remaining pages to browse after the dashboard (no dashboards here)
const ROUTES = {
    admin: [
        "/check-ins",
        "/projects",
        "/calendar",
        "/chat",
        "/online-docs",
        "/ai-workspaces",
    ],
    staff: [
        "/projects",
        "/calendar",
        "/chat",
        "/online-docs",
        "/ai-workspaces",
    ],
    user: [
        "/management/tasks",
        "/calendar",
        "/chat",
        "/online-docs",
        "/ai-workspaces",
    ],
};

// Load profile
export const options = {
    scenarios: {
        stress: {
            executor: "ramping-vus",
            startVUs: 0,
            stages: [
                { duration: "1m", target: 100 }, // warm-up
                { duration: "2m", target: 300 }, // ramp
                { duration: "2m", target: 500 }, // ramp to peak
                { duration: "2m", target: 500 }, // hold peak
                { duration: "1m", target: 0 }, // cool-down
            ],
        },
    },

    thresholds: {
        http_req_duration:    ["p(95)<3000"],
        http_req_failed:      ["rate<0.05"],   // counts both errors AND timeouts
        error_rate:           ["rate<0.05"],
        login_duration:       ["p(95)<2000"],
        dashboard_duration:   ["p(95)<3000"],
        project_duration:     ["p(95)<3000"],
        admin_route_duration: ["p(95)<3000"],
        staff_route_duration: ["p(95)<3000"],
        user_route_duration:  ["p(95)<3000"],
    },

    // Fail fast: don't let VUs pile up waiting 60s (k6 default).
    // 15s is enough headroom for a slow page; anything longer is a hang.
    noConnectionReuse: false,
};

// Helpers

/** Extract Laravel CSRF token from a page's HTML. */
function csrf(html) {
    const meta = html.match(/<meta name="csrf-token" content="([^"]+)"/);
    if (meta) return meta[1];
    const input = html.match(/name="_token"[^>]+value="([^"]+)"/);
    if (input) return input[1];
    return "";
}

function record(res, trend = null) {
    totalRequests.add(1);
    const ok = res.status >= 200 && res.status < 400;
    errorRate.add(!ok);
    if (trend) trend.add(res.timings.duration);
    return ok;
}

/** Pick the role-specific trend metric. */
function trendForRole(role) {
    if (role === "admin") return adminDuration;
    if (role === "staff") return staffDuration;
    return userDuration;
}

function formHeaders() {
    return { "Content-Type": "application/x-www-form-urlencoded" };
}

// Main VU loop
export default function () {
    // Each VU picks a user based on its ID so credentials are distributed evenly
    const user = USERS[__VU % USERS.length];
    const routes = ROUTES[user.role];
    const trend = trendForRole(user.role);

    let token = "";

    // 1. GET /login
    group("GET /login", () => {
        const res = http.get(`${BASE}/login`);
        record(res);
        check(res, { "login page 200": (r) => r.status === 200 });
        token = csrf(res.body);
    });

    if (!token) {
        errorRate.add(1);
        return;
    }
    sleep(0.5);

    // 2. POST /login — redirects:0 so k6 does NOT follow the 302 with POST.
    //    We read the Location header ourselves and do a manual GET below.
    let loginOk = false;
    let dashboardUrl = `${BASE}${DASHBOARD[user.role]}`;
    group(`POST /login [${user.role}]`, () => {
        const res = http.post(
            `${BASE}/login`,
            `_token=${encodeURIComponent(token)}&email=${encodeURIComponent(user.email)}&password=${encodeURIComponent(user.password)}&remember=0`,
            { headers: formHeaders(), redirects: 0, timeout: "15s" },
        );
        record(res, loginDuration);
        // Laravel returns 302 on success; staying on /login means wrong credentials
        loginOk = res.status === 302;
        const location = res.headers["Location"] || res.headers["location"] || "";
        if (loginOk && location) {
            dashboardUrl = location.startsWith("http") ? location : `${BASE}${location}`;
        }
        check(res, {
            [`login [${user.role}]: 302 redirect`]: (r) => r.status === 302,
            [`login [${user.role}]: not staying on /login`]: () =>
                !location.includes("/login"),
        });
    });

    if (!loginOk) return;
    sleep(0.3);

    // 3. GET dashboard — explicit GET to the redirect target
    group(`GET dashboard [${user.role}]`, () => {
        const res = http.get(dashboardUrl, { redirects: 3, timeout: "15s" });
        record(res, dashDuration);
        check(res, {
            [`dashboard 200`]: (r) => r.status === 200,
            [`dashboard not /login`]: (r) => !r.url.includes("/login"),
        });
        if (res.body) token = csrf(res.body) || token;
    });
    sleep(0.5);

    // 5. Navigate role-specific routes (no dashboards — handled above)
    for (const route of routes) {
        group(`GET ${route} [${user.role}]`, () => {
            let pageTrend = trend;
            if (route.includes("projects")) pageTrend = projectDuration;

            const res = http.get(`${BASE}${route}`, { redirects: 3, timeout: "15s" });
            record(res, pageTrend);
            check(res, {
                [`${route} 200`]: (r) => r.status === 200,
                [`${route} not redirected to login`]: (r) =>
                    !r.url.includes("/login"),
            });
            if (res.body) token = csrf(res.body) || token;
        });
        // Random think time between pages (0.3s – 0.7s)
        sleep(0.3 + Math.random() * 0.4);
    }

    // 4. POST /logout
    group("POST /logout", () => {
        const res = http.post(
            `${BASE}/logout`,
            `_token=${encodeURIComponent(token)}`,
            { headers: formHeaders(), redirects: 3 },
        );
        record(res);
        check(res, { "logout → /login": (r) => r.url.includes("/login") });
    });

    sleep(1);
}
