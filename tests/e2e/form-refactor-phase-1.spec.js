const { test, expect } = require("@playwright/test");
const { execFileSync } = require("child_process");
const fs = require("fs");
const path = require("path");

const appRoot = path.resolve(__dirname, "../..");
const workspaceRoot = path.resolve(appRoot, "..");
const packageDevWrapper = path.join(workspaceRoot, "bin/docker-compose-packages-dev.sh");
const supportScript = "/app/tests/e2e/support/form-refactor-phase-1.php";

let fixture;

test.describe.configure({ timeout: 180_000 });

test.beforeEach(() => {
	fixture = runSupport("setup");
});

test("user login form accepts valid credentials and rejects invalid credentials", async ({ page }) => {
	await page.goto(fixture.urls.login);

	let form = await visibleForm(page);
	await form.getByLabel(/^Username$/).fill(fixture.credentials.admin_username);
	await form.getByLabel(/^Password$/).fill("wrong-password");
	await form.locator('button[name="submit_button"][value="save"]').click();

	form = await visibleForm(page);
	await expect(page.locator("body")).toContainText("Wrong username or password");

	await form.getByLabel(/^Username$/).fill(fixture.credentials.admin_username);
	await form.getByLabel(/^Password$/).fill(fixture.credentials.admin_password);
	await form.locator('button[name="submit_button"][value="save"]').click();
	await page.waitForLoadState("domcontentloaded");

	await expectPath(page, "/");
});

test("form submit rejects missing and stale csrf tokens without processing", async ({ page }) => {
	await page.goto(fixture.urls.login);

	let form = await visibleForm(page);
	await expect(form.locator('input[name="csrf_token"]')).toHaveValue(/.+/);
	await form.locator('input[name="csrf_token"]').evaluate((input) => input.remove());
	await form.getByLabel(/^Username$/).fill(fixture.credentials.admin_username);
	await form.getByLabel(/^Password$/).fill(fixture.credentials.admin_password);
	await form.locator('button[name="submit_button"][value="save"]').click();

	await expect(page.locator("body")).toContainText("Refresh this page");

	const before = runSupport("user-by-id", String(fixture.ids.edit_user_id));
	form = await openAsAdmin(page, fixture.urls.user_update);
	await form.locator('input[name="csrf_token"]').evaluate((input) => {
		input.value = "stale-token";
	});
	await form.getByLabel(/^Timezone$/).fill("Asia/Tokyo");
	await form.locator('button[name="submit_button"][value="save"]').click();

	await expect(page.locator("body")).toContainText("Refresh this page");
	expect(runSupport("user-by-id", String(fixture.ids.edit_user_id)).timezone).toBe(before.timezone);
});

test("user form create, update, cancel, invalid, and ACL-denied paths are stable", async ({ page }) => {
	const createdUsername = `fp1_user_${Date.now().toString(36)}`;

	let form = await openAsAdmin(page, fixture.urls.user_create);
	await form.getByLabel(/^Username$/).fill(createdUsername);
	await form.getByLabel(/^Password$/).fill("phase1-secret");
	await form.getByLabel(/^Confirm password$/).fill("phase1-secret");
	await form.getByLabel(/^Timezone$/).fill("Europe/Budapest");
	await form.getByLabel(/^Language$/).selectOption("en-US");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expectPath(page, "/admin/users/");
	expect(runSupport("user-by-username", createdUsername).username).toBe(createdUsername);

	form = await openAsAdmin(page, fixture.urls.user_update);
	await form.getByLabel(/^Timezone$/).fill("Europe/Budapest");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expectPath(page, "/admin/users/");
	expect(runSupport("user-by-id", String(fixture.ids.edit_user_id)).timezone).toBe("Europe/Budapest");

	form = await openAsAdmin(page, fixture.urls.user_update);
	await form.getByLabel(/^Timezone$/).fill("Asia/Tokyo");
	await form.locator('button[name="submit_button"][value="cancel"]').click();
	await expectPath(page, "/admin/users/");
	expect(runSupport("user-by-id", String(fixture.ids.edit_user_id)).timezone).toBe("Europe/Budapest");

	form = await openAsAdmin(page, fixture.urls.user_create);
	await form.getByLabel(/^Username$/).fill("");
	await form.getByLabel(/^Password$/).fill("one");
	await form.getByLabel(/^Confirm password$/).fill("two");
	await form.getByLabel(/^Timezone$/).fill("Not/A_Timezone");
	await form.getByLabel(/^Language$/).selectOption("en-US");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expect(await visibleForm(page)).toBeVisible();
	await expect(page.locator("body")).toContainText(/Required|Passwords do not match|Invalid timezone/);

	await openAsDenied(page, fixture.urls.user_create);
	await expect(page.locator("form.sdui-form")).toHaveCount(0);
	await expect(page.locator("body")).toContainText("You do not have permission");
});

test("widget connection settings form update, cancel, missing item, and ACL-denied paths are stable", async ({ page }) => {
	let form = await openAsAdmin(page, fixture.urls.widget_settings);
	await form.getByLabel(/^Content width$/).selectOption("half");
	await form.getByLabel(/^Last in row$/).check();
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expectPath(page, "/admin/widget-preview.html");

	let settings = runSupport("widget-settings", String(fixture.ids.widget_connection_id));
	expect(settings.widget_width).toBe("half");
	expect(settings.is_last).toBe("1");

	form = await openAsAdmin(page, fixture.urls.widget_settings);
	await form.getByLabel(/^Content width$/).selectOption("full");
	await form.getByLabel(/^Last in row$/).uncheck();
	await form.locator('button[name="submit_button"][value="cancel"]').click();
	await expectPath(page, "/admin/widget-preview.html");

	settings = runSupport("widget-settings", String(fixture.ids.widget_connection_id));
	expect(settings.widget_width).toBe("half");
	expect(settings.is_last).toBe("1");

	await openAsAdmin(page, fixture.urls.widget_settings_missing_item, false);
	await expect(page.locator("form.sdui-form")).toHaveCount(0);
	await expect(page.locator("body")).toContainText("Missing required URL parameters");

	await openAsDenied(page, fixture.urls.widget_settings);
	await expect(page.locator("form.sdui-form")).toHaveCount(0);
	await expect(page.locator("body")).toContainText("You do not have permission");
});

test("admin menu item form create, update, cancel, invalid, and ACL-denied paths are stable", async ({ page }) => {
	const createdLabel = `E2E Phase 1 created ${Date.now()}`;

	let form = await openAsAdmin(page, fixture.urls.adminmenu_create);
	await form.getByLabel(/^Display text$/).fill(createdLabel);
	await form.locator('input[type="radio"][value="kulso"]').check();
	await form.getByLabel(/^Custom URL$/).fill("https://example.test/created");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expectPath(page, "/admin/components/adminmenu/");
	expect(runSupport("adminmenu-by-id", String(fixture.ids.adminmenu_node_id)).node_name).toBe("E2E Phase 1 editable menu");

	form = await openAsAdmin(page, fixture.urls.adminmenu_update);
	await form.getByLabel(/^Display text$/).fill("E2E Phase 1 updated menu");
	await form.locator('input[type="radio"][value="kulso"]').check();
	await form.getByLabel(/^Custom URL$/).fill("https://example.test/updated");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expectPath(page, "/admin/components/adminmenu/");
	let menu = runSupport("adminmenu-by-id", String(fixture.ids.adminmenu_node_id));
	expect(menu.node_name).toBe("E2E Phase 1 updated menu");
	expect(menu.url).toBe("https://example.test/updated");

	form = await openAsAdmin(page, fixture.urls.adminmenu_update);
	await form.getByLabel(/^Display text$/).fill("E2E Phase 1 cancelled menu");
	await form.locator('button[name="submit_button"][value="cancel"]').click();
	await expectPath(page, "/admin/components/adminmenu/");
	expect(runSupport("adminmenu-by-id", String(fixture.ids.adminmenu_node_id)).node_name).toBe("E2E Phase 1 updated menu");

	form = await openAsAdmin(page, fixture.urls.adminmenu_create);
	await form.getByLabel(/^Display text$/).fill("");
	await form.getByLabel(/^Custom URL$/).fill("");
	await form.locator('button[name="submit_button"][value="save"]').click();
	await expect(await visibleForm(page)).toBeVisible();
	await expect(page.locator("body")).toContainText(/Required|Choose whether the link points|Select a page or enter a URL/);

	await openAsDenied(page, fixture.urls.adminmenu_update);
	await expect(page.locator("form.sdui-form")).toHaveCount(0);
	await expect(page.locator("body")).toContainText("You do not have permission");
});

async function visibleForm(page) {
	const form = page.locator("form.sdui-form").first();
	await expect(form).toBeVisible();

	return form;
}

async function openAsAdmin(page, url, expectForm = true) {
	await loginAs(page, fixture.credentials.admin_username, fixture.credentials.admin_password, url);
	await page.goto(url);

	if (!expectForm) {
		return null;
	}

	return visibleForm(page);
}

async function openAsDenied(page, url) {
	await loginAs(page, fixture.credentials.denied_username, fixture.credentials.denied_password, url);
	await page.goto(url);
	await page.waitForLoadState("domcontentloaded");
}

async function loginAs(page, username, password, referer) {
	await page.context().clearCookies();
	await page.goto(`/login.html?loginreferer=${encodeURIComponent(referer)}`, { waitUntil: "domcontentloaded" });
	const form = await visibleForm(page);
	await form.getByLabel(/^Username$/).fill(username);
	await form.getByLabel(/^Password$/).fill(password);
	await form.locator('button[name="submit_button"][value="save"]').click();
	await page.waitForLoadState("domcontentloaded");
}

async function expectPath(page, pathname) {
	await expect.poll(() => new URL(page.url()).pathname).toBe(pathname);
}

function runSupport(command, ...args) {
	const commandArgs = fs.existsSync(packageDevWrapper)
		? ["radaptor-app-skeleton", "exec", "-T", "-e", "XDEBUG_MODE=off", "php", "php", supportScript, command, ...args]
		: ["compose", "-f", "docker-compose-dev.yml", "exec", "-T", "-e", "XDEBUG_MODE=off", "php", "php", supportScript, command, ...args];
	const executable = fs.existsSync(packageDevWrapper) ? packageDevWrapper : "docker";
	const output = execFileSync(executable, commandArgs, {
		cwd: appRoot,
		encoding: "utf8",
		stdio: ["ignore", "pipe", "pipe"],
	});

	return JSON.parse(output);
}
