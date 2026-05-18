<?php

require_once dirname(__DIR__, 3) . '/bootstrap/bootstrap.php';

const FORM_PHASE_1_PASSWORD = 'password123';
const FORM_PHASE_1_CREATED_PREFIX = 'fp1_user_';
const FORM_PHASE_1_LEGACY_CREATED_PREFIX = 'form_phase1_created_browser_';
const FORM_PHASE_1_DENIED_USER = 'form_phase1_denied_browser';
const FORM_PHASE_1_EDIT_USER = 'form_phase1_edit_browser';
const FORM_PHASE_1_WIDGET_TARGET_PATH = '/e2e/form-phase-1-widget-target.html';
const FORM_PHASE_1_ADMINMENU_NODE_PREFIX = 'E2E Phase 1 ';
const FORM_PHASE_1_ADMINMENU_NODE = FORM_PHASE_1_ADMINMENU_NODE_PREFIX . 'editable menu';

$command = $argv[1] ?? 'setup';

try {
	$result = match ($command) {
		'setup' => setupFormPhase1E2eData(),
		'user-by-username' => userByUsername((string)($argv[2] ?? '')),
		'user-by-id' => userById((int)($argv[2] ?? 0)),
		'widget-settings' => widgetSettings((int)($argv[2] ?? 0)),
		'adminmenu-by-id' => adminmenuById((int)($argv[2] ?? 0)),
		default => throw new InvalidArgumentException("Unknown command: {$command}"),
	};

	echo json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $throwable) {
	fwrite(STDERR, $throwable::class . ': ' . $throwable->getMessage() . PHP_EOL);

	exit(1);
}

/**
 * @return array<string, mixed>
 */
function setupFormPhase1E2eData(): array
{
	cleanupCreatedUsers();
	cleanupAdminmenuSmokeNodes();

	$denied_user_id = ensureUser(FORM_PHASE_1_DENIED_USER, [
		'timezone' => 'UTC',
		'locale' => 'en-US',
	]);
	resetUserAssignments($denied_user_id);

	$edit_user_id = ensureUser(FORM_PHASE_1_EDIT_USER, [
		'timezone' => 'UTC',
		'locale' => 'en-US',
	]);

	$widget_connection_id = ensureWidgetSettingsTarget();
	WidgetSettings::saveSettings([
		'widget_width' => 'full',
		'is_last' => '0',
	], $widget_connection_id);

	$adminmenu_page_id = ensureFormPage(
		'/admin/components/adminmenu/edit/index.html',
		FormList::ADMINMENUMENUELEMENT
	);
	$adminmenu_node_id = ensureAdminmenuNode();

	foreach ([
		ResourceTypeWebpage::getWebpageIdByFormType(FormList::USER),
		ResourceTypeWebpage::getWebpageIdByFormType(FormList::WIDGETCONNECTIONSETTINGS),
		$adminmenu_page_id,
	] as $page_id) {
		if (is_int($page_id) && $page_id > 0) {
			allowPageViewForUser($page_id, $denied_user_id);
		}
	}

	return [
		'credentials' => [
			'admin_username' => getenv('E2E_BOOTSTRAP_ADMIN_USERNAME') ?: 'admin',
			'admin_password' => getenv('E2E_BOOTSTRAP_ADMIN_PASSWORD') ?: 'admin123456',
			'denied_username' => FORM_PHASE_1_DENIED_USER,
			'denied_password' => FORM_PHASE_1_PASSWORD,
		],
		'ids' => [
			'edit_user_id' => $edit_user_id,
			'widget_connection_id' => $widget_connection_id,
			'adminmenu_node_id' => $adminmenu_node_id,
		],
		'urls' => [
			'login' => '/login.html',
			'user_create' => Form::getSeoUrl(FormList::USER, null, '/admin/users/'),
			'user_update' => Form::getSeoUrl(FormList::USER, $edit_user_id, '/admin/users/'),
			'widget_settings' => Form::getSeoUrl(FormList::WIDGETCONNECTIONSETTINGS, $widget_connection_id, '/admin/widget-preview.html'),
			'widget_settings_missing_item' => (string)Url::getSeoUrl((int)ResourceTypeWebpage::getWebpageIdByFormType(FormList::WIDGETCONNECTIONSETTINGS)),
			'adminmenu_create' => Form::getSeoUrl(FormList::ADMINMENUMENUELEMENT, null, '/admin/components/adminmenu/', ['ref_id' => 0]),
			'adminmenu_update' => Form::getSeoUrl(FormList::ADMINMENUMENUELEMENT, $adminmenu_node_id, '/admin/components/adminmenu/'),
		],
	];
}

function cleanupAdminmenuSmokeNodes(): void
{
	$nodes = DbHelper::selectManyFromQuery(
		'SELECT node_id FROM adminmenu_tree WHERE node_name LIKE ? ORDER BY lft DESC',
		[FORM_PHASE_1_ADMINMENU_NODE_PREFIX . '%']
	);

	foreach ($nodes as $node) {
		$node_id = (int)($node['node_id'] ?? 0);

		if ($node_id > 0) {
			AdminMenu::deleteRecursive($node_id);
		}
	}
}

function cleanupCreatedUsers(): void
{
	$users = DbHelper::selectManyFromQuery(
		'SELECT user_id FROM users WHERE username LIKE ? OR username LIKE ?',
		[
			FORM_PHASE_1_CREATED_PREFIX . '%',
			FORM_PHASE_1_LEGACY_CREATED_PREFIX . '%',
		]
	);

	foreach ($users as $user) {
		$user_id = (int)($user['user_id'] ?? 0);

		if ($user_id <= 0) {
			continue;
		}

		DbHelper::prexecute('DELETE FROM users_roles_mapping WHERE user_id=?', [$user_id]);
		DbHelper::prexecute('DELETE FROM users_usergroups_mapping WHERE user_id=?', [$user_id]);
		DbHelper::prexecute("DELETE FROM resource_acl WHERE subject_type='user' AND subject_id=?", [$user_id]);
		DbHelper::prexecute('DELETE FROM users WHERE user_id=?', [$user_id]);
	}
}

/**
 * @param array<string, mixed> $overrides
 */
function ensureUser(string $username, array $overrides = []): int
{
	$user = DbHelper::selectOne('users', ['username' => $username]);
	$savedata = [
		'username' => $username,
		'password' => User::encodePassword(FORM_PHASE_1_PASSWORD),
		'is_active' => 1,
		'locale' => 'en-US',
		'timezone' => 'UTC',
	] + $overrides;

	if (is_array($user)) {
		User::updateUser($savedata, (int)$user['user_id']);

		return (int)$user['user_id'];
	}

	$user_id = User::addUser($savedata);

	if (!is_int($user_id) || $user_id <= 0) {
		throw new RuntimeException("Unable to create e2e user {$username}");
	}

	return $user_id;
}

function resetUserAssignments(int $user_id): void
{
	DbHelper::prexecute('DELETE FROM users_roles_mapping WHERE user_id=?', [$user_id]);
	DbHelper::prexecute('DELETE FROM users_usergroups_mapping WHERE user_id=?', [$user_id]);
}

function ensureWidgetSettingsTarget(): int
{
	CmsResourceSpecService::upsertWebpage([
		'path' => FORM_PHASE_1_WIDGET_TARGET_PATH,
		'layout' => 'public_empty',
		'slots' => [
			ResourceTypeWebpage::DEFAULT_SLOT_NAME => [
				[
					'widget' => WidgetList::PLAINHTML,
					'settings' => [
						'content' => '<p>Form Phase 1 widget settings e2e target</p>',
					],
				],
			],
		],
		'replace_slots' => true,
	]);

	$page = CmsPathHelper::resolveWebpage(FORM_PHASE_1_WIDGET_TARGET_PATH);

	if (!is_array($page)) {
		throw new RuntimeException('Unable to resolve widget settings target page.');
	}

	$connection_id = Widget::getWidgetConnectionId((int)$page['node_id'], ResourceTypeWebpage::DEFAULT_SLOT_NAME, WidgetList::PLAINHTML);

	if (!is_int($connection_id) || $connection_id <= 0) {
		throw new RuntimeException('Unable to resolve widget settings target connection.');
	}

	return $connection_id;
}

function ensureFormPage(string $path, string $form_id): int
{
	// The admin form page lives under a protected path; e2e setup needs it present before ACL assertions.
	$page_id = ResourceTreeHandler::withProtectedResourceMutationBypass(
		static fn (): int => CmsResourceSpecService::upsertWebpage([
			'path' => $path,
			'layout' => 'admin_default',
			'slots' => [
				ResourceTypeWebpage::DEFAULT_SLOT_NAME => [
					[
						'widget' => WidgetList::FORM,
						'attributes' => [
							'form_id' => $form_id,
						],
					],
				],
			],
			'replace_slots' => true,
		])
	);

	if ($page_id <= 0) {
		throw new RuntimeException("Unable to ensure form page {$path}");
	}

	return $page_id;
}

function ensureAdminmenuNode(): int
{
	$existing = DbHelper::selectOne('adminmenu_tree', ['node_name' => FORM_PHASE_1_ADMINMENU_NODE]);
	$savedata = [
		'node_name' => FORM_PHASE_1_ADMINMENU_NODE,
		'page_id' => null,
		'url' => 'https://example.test/form-phase-1',
	];

	if (is_array($existing)) {
		AdminMenu::updateMenu($savedata, (int)$existing['node_id']);

		return (int)$existing['node_id'];
	}

	if (!AdminMenu::addMenu($savedata, 0)) {
		throw new RuntimeException('Unable to create e2e admin menu node.');
	}

	$new = DbHelper::selectOne('adminmenu_tree', ['node_name' => FORM_PHASE_1_ADMINMENU_NODE]);

	if (!is_array($new)) {
		throw new RuntimeException('Unable to resolve e2e admin menu node.');
	}

	return (int)$new['node_id'];
}

function allowPageViewForUser(int $page_id, int $user_id): void
{
	$row = DbHelper::selectOne('resource_acl', [
		'resource_id' => $page_id,
		'subject_type' => 'user',
		'subject_id' => $user_id,
	]);
	$savedata = [
		'resource_id' => $page_id,
		'subject_type' => 'user',
		'subject_id' => $user_id,
		'allow_view' => 1,
		'allow_edit' => 0,
		'allow_delete' => 0,
		'allow_publish' => 0,
		'allow_list' => 1,
		'allow_create' => 0,
	];

	if (is_array($row)) {
		$savedata['acl_id'] = (int)$row['acl_id'];
		DbHelper::updateHelper('resource_acl', $savedata, (int)$row['acl_id']);

		return;
	}

	DbHelper::insertHelper('resource_acl', $savedata);
}

/**
 * @return array<string, mixed>|null
 */
function userByUsername(string $username): ?array
{
	return DbHelper::selectOne('users', ['username' => $username]);
}

/**
 * @return array<string, mixed>|null
 */
function userById(int $user_id): ?array
{
	if ($user_id <= 0) {
		return null;
	}

	return DbHelper::selectOne('users', ['user_id' => $user_id]);
}

/**
 * @return array<string, mixed>
 */
function widgetSettings(int $connection_id): array
{
	if ($connection_id <= 0) {
		return [];
	}

	return WidgetSettings::getSettings($connection_id);
}

/**
 * @return array<string, mixed>|null
 */
function adminmenuById(int $node_id): ?array
{
	if ($node_id <= 0) {
		return null;
	}

	return DbHelper::selectOne('adminmenu_tree', ['node_id' => $node_id]);
}
