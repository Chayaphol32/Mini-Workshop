# Premium Cinematic Coffee Performance Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** ทำให้ Coffee Member Rewards ตอบสนองเร็วขึ้นอย่างวัดผลได้ และยกระดับ UI/UX ของ Guest, User และ Admin ให้เป็นธีม Reserve Coffee Club แบบ premium พร้อม Cinematic Motion ที่ยัง responsive และเข้าถึงได้

**Architecture:** คง PHP server-rendered + TiDB + Vercel เดิม ใช้ lazy database bootstrap สำหรับ request ที่ไม่ต้อง query, versioned static assets และ CSS-first progressive enhancement ใน shared shell กับหน้าหลัก โดยไม่ย้าย auth, role, CSRF หรือการคำนวณราคาไป client

**Tech Stack:** PHP 8.x, PDO MySQL/TiDB, HTML5, CSS3, Vercel PHP runtime, existing repository/auth/order tests

## Global Constraints

- ห้ามเปลี่ยน schema, ลบข้อมูล หรือเปลี่ยนขอบเขตข้อมูลของ User/Admin
- ต้องคง CSRF, session cookie flags, server-side authorization, server-side price และ order lifecycle เดิม
- ห้ามเพิ่ม external font, image, video, JavaScript framework หรือ animation package ในรอบนี้
- animation ใช้ transform, opacity, box-shadow และสีเป็นหลัก พร้อม prefers-reduced-motion
- ห้ามใส่ credential, connection string หรือ secret ลง source, test output, commit หรือคำตอบ
- เพิ่มไฟล์ runtime ชั่วคราวลง .gitignore และ stage เฉพาะไฟล์ของงาน
- ทุก task ต้องเริ่มด้วย test ที่ fail ตาม behavior ใหม่, แก้ให้ pass, รัน regression ที่เกี่ยวข้อง และ commit แยก task

## Task 1: Lazy-load database for public GET requests

**Files:**

- Create tests/bootstrap_lazy_test.php
- Modify includes/bootstrap.php
- Modify index.php, login.php, register.php, logout.php

- [ ] Add a test that sets COFFEE_DB_HOST=127.0.0.1, COFFEE_DB_PORT=1, defines COFFEE_SKIP_DATABASE, requires bootstrap, asserts $pdo is not set, and asserts current_user() and csrf_token() are available. Run it with the project PHP binary and verify it fails before the implementation because bootstrap opens PDO eagerly.
- [ ] Change bootstrap to require config/database.php only when COFFEE_SKIP_DATABASE is not defined.
- [ ] Define COFFEE_SKIP_DATABASE before bootstrap in root, login, register and logout entry points. In login and register, require config/database.php immediately before POST-only validation/authentication logic, after the already-authenticated redirect check.
- [ ] Run bootstrap_lazy_test.php, the login/register/auth tests and PHP lint. Commit as perf: skip database for public page requests.

Expected bootstrap guard:

    if (!defined('COFFEE_SKIP_DATABASE')) {
        require_once __DIR__ . '/../config/database.php';
    }

Expected POST-only loading:

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/config/database.php';
    }

## Task 2: Versioned assets and immutable Vercel caching

**Files:**

- Create tests/asset_url_test.php
- Modify includes/functions.php
- Modify includes/header.php
- Modify vercel.json
- Modify tests/vercel_deploy_test.php

- [ ] Add a test that sets COFFEE_APP_BASE=coffee, calls asset_url('assets/style.css'), and asserts the result preserves /coffee/assets/style.css and contains a numeric v query parameter. Run it and verify it fails because asset_url() does not exist.
- [ ] Implement asset_url(string $path): string using app_url() plus the local file modification time when the asset exists, with a safe fallback version of 1.
- [ ] Use asset_url() for the shared stylesheet link.
- [ ] Add a Vercel header rule for /assets/(.*) with Cache-Control: public, max-age=31536000, immutable.
- [ ] Extend deployment tests to validate the asset header and lazy bootstrap source. Run asset and deployment tests and commit as perf: version and cache static assets.

## Task 3: Premium shared shell and responsive navigation

**Files:**

- Create tests/render_shell_test.php
- Modify includes/header.php
- Modify includes/footer.php
- Modify .gitignore to exclude .superpowers/ and local session artifacts

- [ ] Add a rendered GET test that buffers login.php with COFFEE_SKIP_DATABASE, then asserts output contains a skip link, id="main-content", role-guest on body and a native details.nav-menu. Run it and verify it fails against the current shell.
- [ ] Add a semantic brand lockup, role-aware body class, skip link, identity label, and main landmark without changing existing route targets or form methods.
- [ ] Keep existing role-specific links and logout forms, placing them inside a native details menu that can collapse on mobile and remain expanded visually on desktop.
- [ ] Add a footer inner wrapper and preserve the existing footer text.
- [ ] Run the rendered shell test and lint. Commit as ui: build premium responsive app shell.

## Task 4: Cinematic CSS and User/Admin hierarchy

**Files:**

- Modify assets/style.css
- Modify user/index.php
- Modify user/menu.php
- Modify admin/index.php
- Extend tests/render_shell_test.php for key page classes

- [ ] Add failing assertions for premium page classes on the user dashboard, user menu and admin dashboard render contracts.
- [ ] Replace the basic visual tokens with espresso, cream, copper, paper, ink, muted, success and danger variables while retaining selectors required by forms, tables, order cards and auth pages.
- [ ] Add responsive layout polish for cards, KPI/stat blocks, tables, menu cards, forms, order summaries and sticky order action panels.
- [ ] Add page-enter, reveal-up, float-mark, soft-glow and limited CTA shine animations. Animate only compositor-friendly properties and stagger repeated cards with a small maximum delay.
- [ ] Add visible keyboard focus, skip-link styling, role-specific header accents, mobile details menu behavior and prefers-reduced-motion overrides.
- [ ] Add page-heading-premium and hero-card to the main user/admin surfaces without altering their data queries or actions.
- [ ] Run rendered tests and lint, inspect the login page at desktop and narrow widths, then commit as ui: add cinematic coffee club visual system.

## Task 5: Optional persistent TiDB connections

**Files:**

- Modify config/database.php
- Modify tests/vercel_deploy_test.php

- [ ] Add a deployment assertion that the TiDB branch reads TIDB_PERSISTENT and supports PDO::ATTR_PERSISTENT. Run it and verify it fails before implementation.
- [ ] Add PDO::ATTR_PERSISTENT=true only in the TiDB branch when TIDB_PERSISTENT parses truthy, defaulting to enabled for warm serverless instances; allow TIDB_PERSISTENT=0 as a rollback switch.
- [ ] Run the deployment test and PHP lint. Commit as perf: support persistent TiDB connections.

Expected option behavior:

    $persistent = filter_var(getenv('TIDB_PERSISTENT') ?: '1', FILTER_VALIDATE_BOOLEAN);
    if ($persistent) {
        $pdoOptions[PDO::ATTR_PERSISTENT] = true;
    }

## Task 6: Full verification and production release

**Files:** no new product files; use the existing test suite and deployment configuration

- [ ] Run PHP lint across every tracked PHP file using the project PHP binary.
- [ ] Run all existing tests plus bootstrap_lazy_test.php, asset_url_test.php and render_shell_test.php.
- [ ] Confirm git diff --check, inspect the final diff, and verify .superpowers/ is not staged.
- [ ] Push the task commits to origin main, wait for Vercel production deployment to become ready, and do not expose any secret while checking status.
- [ ] Verify production root redirects to /login.php, login GET is 200 without PHP warnings, CSS is 200 with immutable caching, and measure login response timing at least once after deployment.
- [ ] Verify User and Admin login still land on separate dashboards and retain their existing data visibility. Check desktop and mobile layout for horizontal overflow and reduced-motion behavior.
- [ ] Run the completion checklist and report the production URL, actual measured timing, tests, and any cold-start variability honestly.

## Acceptance Criteria

- Public root/login/register/logout GET paths do not open TiDB until a POST or protected data path needs it.
- Versioned CSS is served with a one-year immutable cache policy.
- Shared shell is semantic, keyboard-visible, mobile-friendly and role-aware.
- User sees only their own rewards/orders; Admin retains all existing management visibility and actions.
- Cinematic motion is noticeable but does not block content, and reduced-motion removes nonessential movement.
- All automated tests and PHP lint pass; production endpoints return the expected status and headers.
