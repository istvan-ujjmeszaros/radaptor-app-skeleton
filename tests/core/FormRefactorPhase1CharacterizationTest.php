<?php

final class FormRefactorPhase1CharacterizationTest extends TransactionedTestCase
{
	private const int ADMINMENU_UPDATE_FIXTURE_ID = 9002;
	private const int WIDGET_CONNECTION_SETTINGS_FIXTURE_ID = 9083;

	private array $originalGet = [];
	private array $originalPost = [];
	private array $originalServer = [];

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalGet = $_GET;
		$this->originalPost = $_POST;
		$this->originalServer = $_SERVER;

		$this->setRequestContext();
		$this->impersonate(null);
	}

	#[\Override]
	protected function tearDown(): void
	{
		$_GET = $this->originalGet;
		$_POST = $this->originalPost;
		$_SERVER = $this->originalServer;
		RequestContextHolder::initializeRequest(get: $_GET, post: $_POST, server: $_SERVER);
		$this->impersonate(null);

		parent::tearDown();
	}

	public function testUserLoginBuildTreeMatchesCurrentStructure(): void
	{
		$form = $this->createForm(FormList::USERLOGIN, 'phase1_login');

		$this->assertSame([
			'type' => 'widget',
			'component' => 'form',
			'props' => [
				'form_id' => 'fphase1_login',
				'form_instance_id' => 'phase1_login',
				'form_descriptor_id' => FormList::USERLOGIN,
				'form_name' => FormList::USERLOGIN,
				'mode' => AbstractForm::_MODE_CREATE,
				'action' => '/?context=form&event=submit',
				'method' => 'post',
				'autocomplete' => true,
				'focusable' => true,
				'post_javascript_file' => '',
				'button_save_class' => FormButton::CLASS_STANDARD,
				'button_cancel_class' => null,
				'field_refs' => $this->expectedFieldRefs('phase1_login', ['username', 'password']),
				'submit_context' => $this->expectedSubmitContext(FormList::USERLOGIN, 'phase1_login'),
			],
			'hidden_fields' => ['form.input.hidden'],
			'rows' => [
				[
					'component' => 'form.row',
					'row_id' => 'row_fphase1_login_input_1',
					'inputs' => [
						[
							'component' => 'form.input.text',
							'fieldname' => 'username',
							'input_type' => 'text',
							'field_key' => 'username',
							'data_field_key' => 'username',
							'name' => 'username',
							'save' => true,
							'required' => null,
							'validators' => [],
						],
					],
				],
				[
					'component' => 'form.row',
					'row_id' => 'row_fphase1_login_input_2',
					'inputs' => [
						[
							'component' => 'form.input.password',
							'fieldname' => 'password',
							'input_type' => 'password',
							'field_key' => 'password',
							'data_field_key' => 'password',
							'name' => 'password',
							'save' => true,
							'required' => null,
							'validators' => [],
						],
					],
				],
			],
		], $this->normalizeFormTree($form));
	}

	public function testUserBuildTreeMatchesCurrentCreateStructure(): void
	{
		$form = $this->createForm(FormList::USER, 'phase1_user');

		$this->assertSame([
			'type' => 'widget',
			'component' => 'form',
			'props' => [
				'form_id' => 'fphase1_user',
				'form_instance_id' => 'phase1_user',
				'form_descriptor_id' => FormList::USER,
				'form_name' => FormList::USER,
				'mode' => AbstractForm::_MODE_CREATE,
				'action' => '/?context=form&event=submit',
				'method' => 'post',
				'autocomplete' => false,
				'focusable' => true,
				'post_javascript_file' => '',
				'button_save_class' => FormButton::CLASS_POSITIVE,
				'button_cancel_class' => FormButton::CLASS_NEGATIVE,
				'field_refs' => $this->expectedFieldRefs('phase1_user', ['username', 'passwd1', 'passwd2', 'timezone', 'locale']),
				'submit_context' => $this->expectedSubmitContext(FormList::USER, 'phase1_user'),
			],
			'hidden_fields' => ['form.input.hidden'],
			'rows' => [
				$this->expectedInputRow('fphase1_user_input_1', 'form.input.text', 'username', 'text', true, null, ['NotEmpty', 'Stringlength']),
				$this->expectedInputRow('fphase1_user_input_2', 'form.input.password', 'passwd1', 'password', false, null, ['Stringlength']),
				$this->expectedInputRow('fphase1_user_input_3', 'form.input.password', 'passwd2', 'password', false, null, ['Stringlength']),
				$this->expectedInputRow('fphase1_user_input_4', 'form.input.text', 'timezone', 'text', true, null, ['Stringlength']),
				$this->expectedInputRow('fphase1_user_input_5', 'form.input.select', 'locale', 'select', true, true, []),
			],
		], $this->normalizeFormTree($form));
	}

	public function testWidgetConnectionSettingsBuildTreeMatchesCurrentUpdateStructure(): void
	{
		$fixture_id = $this->widgetConnectionSettingsFixtureId();
		$form = $this->createForm(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_widget_settings', [
			'item_id' => (string)$fixture_id,
		]);

		$this->assertSame('three_fourth', $form->getInput('widget_width')?->getValue());
		$this->assertNull($form->getInput('is_last')?->getValue());

		$this->assertSame([
			'type' => 'widget',
			'component' => 'form',
			'props' => [
				'form_id' => 'fphase1_widget_settings',
				'form_instance_id' => 'phase1_widget_settings',
				'form_descriptor_id' => FormList::WIDGETCONNECTIONSETTINGS,
				'form_name' => FormList::WIDGETCONNECTIONSETTINGS,
				'mode' => AbstractForm::_MODE_UPDATE,
				'action' => '/?context=form&event=submit',
				'method' => 'post',
				'autocomplete' => true,
				'focusable' => true,
				'post_javascript_file' => '',
				'button_save_class' => FormButton::CLASS_POSITIVE,
				'button_cancel_class' => FormButton::CLASS_NEGATIVE,
				'field_refs' => $this->expectedFieldRefs('phase1_widget_settings', ['widget_width', 'is_last']),
				'submit_context' => $this->expectedSubmitContext(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_widget_settings', $fixture_id),
			],
			'hidden_fields' => ['form.input.hidden'],
			'rows' => [
				$this->expectedInputRow('fphase1_widget_settings_input_1', 'form.input.select', 'widget_width', 'select', true, true, []),
				$this->expectedInputRow('fphase1_widget_settings_input_2', 'form.input.checkbox', 'is_last', 'checkbox', true, null, []),
			],
		], $this->normalizeFormTree($form));
	}

	public function testAdminMenuElementBuildTreeMatchesCurrentCreateStructure(): void
	{
		$form = $this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu');

		$this->assertSame([
			'type' => 'widget',
			'component' => 'form',
			'props' => [
				'form_id' => 'fphase1_adminmenu',
				'form_instance_id' => 'phase1_adminmenu',
				'form_descriptor_id' => FormList::ADMINMENUMENUELEMENT,
				'form_name' => FormList::ADMINMENUMENUELEMENT,
				'mode' => AbstractForm::_MODE_CREATE,
				'action' => '/?context=form&event=submit',
				'method' => 'post',
				'autocomplete' => true,
				'focusable' => true,
				'post_javascript_file' => '',
				'button_save_class' => FormButton::CLASS_POSITIVE,
				'button_cancel_class' => FormButton::CLASS_NEGATIVE,
				'field_refs' => $this->expectedFieldRefs('phase1_adminmenu', ['node_name', 'type', 'url', 'page_id']),
				'submit_context' => $this->expectedSubmitContext(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu'),
			],
			'hidden_fields' => ['form.input.hidden'],
			'rows' => [
				$this->expectedInputRow('fphase1_adminmenu_input_1', 'form.input.text', 'node_name', 'text', true, null, ['NotEmpty', 'Stringlength']),
				$this->expectedInputRow('fphase1_adminmenu_input_2', 'form.input.radiogroup', 'type', 'radiogroup', true, null, ['Selected']),
				$this->expectedInputRow('fphase1_adminmenu_input_3', 'form.input.text', 'url', 'text', true, null, ['NotEmpty', 'Stringlength']),
				$this->expectedInputRow('fphase1_adminmenu_input_4', 'form.input.select', 'page_id', 'select', true, false, []),
			],
		], $this->normalizeFormTree($form));
	}

	public function testAdminMenuElementBuildTreeMatchesCurrentUpdateStructure(): void
	{
		$fixture_id = $this->adminMenuElementFixtureId();
		$form = $this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu_update', [
			'item_id' => (string)$fixture_id,
		]);

		$this->assertSame('E2E Editable External', $form->getInput('node_name')?->getValue());
		$this->assertSame('kulso', $form->getInput('type')?->getValue());
		$this->assertSame('#', $form->getInput('url')?->getValue());
		$this->assertNull($form->getInput('page_id')?->getValue());

		$this->assertSame([
			'type' => 'widget',
			'component' => 'form',
			'props' => [
				'form_id' => 'fphase1_adminmenu_update',
				'form_instance_id' => 'phase1_adminmenu_update',
				'form_descriptor_id' => FormList::ADMINMENUMENUELEMENT,
				'form_name' => FormList::ADMINMENUMENUELEMENT,
				'mode' => AbstractForm::_MODE_UPDATE,
				'action' => '/?context=form&event=submit',
				'method' => 'post',
				'autocomplete' => true,
				'focusable' => true,
				'post_javascript_file' => '',
				'button_save_class' => FormButton::CLASS_POSITIVE,
				'button_cancel_class' => FormButton::CLASS_NEGATIVE,
				'field_refs' => $this->expectedFieldRefs('phase1_adminmenu_update', ['node_name', 'type', 'url', 'page_id']),
				'submit_context' => $this->expectedSubmitContext(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu_update', $fixture_id),
			],
			'hidden_fields' => ['form.input.hidden'],
			'rows' => [
				$this->expectedInputRow('fphase1_adminmenu_update_input_1', 'form.input.text', 'node_name', 'text', true, null, ['NotEmpty', 'Stringlength']),
				$this->expectedInputRow('fphase1_adminmenu_update_input_2', 'form.input.radiogroup', 'type', 'radiogroup', true, null, ['Selected']),
				$this->expectedInputRow('fphase1_adminmenu_update_input_3', 'form.input.text', 'url', 'text', true, null, ['NotEmpty', 'Stringlength']),
				$this->expectedInputRow('fphase1_adminmenu_update_input_4', 'form.input.select', 'page_id', 'select', true, false, []),
			],
		], $this->normalizeFormTree($form));
	}

	public function testItemIdGetParameterIsTheCurrentCreateUpdateModeSwitch(): void
	{
		$user = DbHelper::selectOne('users', ['username' => 'admin_developer']);
		$this->assertNotNull($user);

		$create = $this->createForm(FormList::USER, 'phase1_user_create');
		$update = $this->createForm(FormList::USER, 'phase1_user_update', [
			'item_id' => (string)$user['user_id'],
		]);

		$this->assertSame(AbstractForm::_MODE_CREATE, $create->getMode());
		$this->assertNull($create->getItemId());
		$this->assertSame(AbstractForm::_MODE_UPDATE, $update->getMode());
		$this->assertSame((int)$user['user_id'], $update->getItemId());
		$this->assertSame('admin_developer', $update->getInput('username')?->getValue());
	}

	public function testProcessPayloadIsKeyedByStableFieldKeysAndIeSaveMarkerIsRejected(): void
	{
		$form = $this->createForm(FormList::USERLOGIN, 'phase1_ie_login', [], [
			'submit_button' => 'legacy image button <!--save--> marker',
			'username' => 'admin_developer',
			'password' => 'not-the-password',
		]);

		$this->assertNull($form->getInput('username')?->getValue());
		$this->assertNull($form->getInput('password')?->getValue());

		$result = $form->process([
			'submit_button' => 'legacy image button <!--save--> marker',
			'username' => 'admin_developer',
			'password' => 'not-the-password',
		]);

		$this->assertTrue($result->isInvalid());
		$this->assertArrayHasKey('submit_button', $result->errors());
		$this->assertSame('admin_developer', $form->getInput('username')?->getValue());
		$this->assertSame('not-the-password', $form->getInput('password')?->getValue());
		$this->assertSame([], $form->savedata);
	}

	public function testCurrentFormTreeExposesSessionCsrfTokenAsHiddenField(): void
	{
		$form = $this->createForm(FormList::USERLOGIN, 'phase3_csrf');
		$tree = $form->buildTree();

		$this->assertArrayNotHasKey('csrf_token', $tree['props']['field_refs']);
		$this->assertArrayNotHasKey('csrf_token', $tree['props']['submit_context']);
		$this->assertCount(1, $tree['slots']['hidden_fields']);
		$csrf = $tree['slots']['hidden_fields'][0]['props'];
		$this->assertSame('csrf_token', $csrf['name']);
		$this->assertSame('csrf_token', $csrf['data_field_key']);
		$this->assertNotSame('', $csrf['value']);
		$this->assertFalse($csrf['save']);
	}

	public function testFormTreeDisablesPersistentCacheWriteWhenIssuingCsrfToken(): void
	{
		$previous = getenv('APP_PERSISTENT_CACHE_ENABLED');
		putenv('APP_PERSISTENT_CACHE_ENABLED=true');

		try {
			$form = $this->createForm(FormList::USERLOGIN, 'phase3_csrf_cache_guard');
			$this->assertTrue(RequestContextHolder::isPersistentCacheWriteEnabled());

			$form->buildTree();

			$this->assertFalse(RequestContextHolder::isPersistentCacheWriteEnabled());
		} finally {
			if ($previous === false) {
				putenv('APP_PERSISTENT_CACHE_ENABLED');
			} else {
				putenv('APP_PERSISTENT_CACHE_ENABLED=' . $previous);
			}
		}
	}

	public function testUserLoginValidationAndSavedataAreCharacterized(): void
	{
		$valid = $this->createForm(FormList::USERLOGIN, 'phase1_login_valid');
		$this->setInputValues($valid, [
			'username' => 'admin_developer',
			'password' => 'password123',
		]);
		$this->validateForm($valid);
		$this->assertTrue($valid->isValid());
		$this->processSavedata($valid);
		$this->assertSame(['username', 'password'], array_keys($valid->savedata));
		$this->assertSame('admin_developer', $valid->savedata['username']);
		$this->assertSame('password123', $valid->savedata['password']);

		$invalid = $this->createForm(FormList::USERLOGIN, 'phase1_login_invalid');
		$this->setInputValues($invalid, [
			'username' => 'admin_developer',
			'password' => 'wrong-password',
		]);
		$this->validateForm($invalid);
		$this->assertFalse($invalid->isValid());
		$this->assertNotEmpty($invalid->getInput('username')?->getErrors());
	}

	public function testUserValidationAndSavedataAreCharacterized(): void
	{
		$valid = $this->createForm(FormList::USER, 'phase1_user_valid');
		$this->setInputValues($valid, [
			'username' => 'phase1_valid_user_' . uniqid(),
			'passwd1' => 'phase1-secret',
			'passwd2' => 'phase1-secret',
			'timezone' => 'Europe/Budapest',
			'locale' => 'en-US',
		]);
		$this->validateForm($valid);
		$this->assertTrue($valid->isValid());
		$this->processSavedata($valid);
		$this->assertSame(['password', 'username', 'timezone', 'locale'], array_keys($valid->savedata));
		$this->assertArrayNotHasKey('passwd1', $valid->savedata);
		$this->assertArrayNotHasKey('passwd2', $valid->savedata);
		$this->assertSame('Europe/Budapest', $valid->savedata['timezone']);
		$this->assertSame('en-US', $valid->savedata['locale']);
		$this->assertTrue(User::verifyPassword('phase1-secret', $valid->savedata['password']));

		$invalid = $this->createForm(FormList::USER, 'phase1_user_invalid');
		$this->setInputValues($invalid, [
			'username' => '',
			'passwd1' => 'one',
			'passwd2' => 'two',
			'timezone' => 'Not/A_Timezone',
			'locale' => 'en-US',
		]);
		$this->validateForm($invalid);
		$this->assertFalse($invalid->isValid());
		$this->assertNotEmpty($invalid->getInput('username')?->getErrors());
		$this->assertNotEmpty($invalid->getInput('passwd2')?->getErrors());
		$this->assertNotEmpty($invalid->getInput('timezone')?->getErrors());
	}

	public function testWidgetConnectionSettingsValidationAndSavedataAreCharacterized(): void
	{
		$fixture_id = $this->widgetConnectionSettingsFixtureId();
		$valid = $this->createForm(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_widget_valid', [
			'item_id' => (string)$fixture_id,
		]);
		$this->setInputValues($valid, [
			'widget_width' => 'full',
			'is_last' => '1',
		]);
		$this->validateForm($valid);
		$this->assertTrue($valid->isValid());
		$this->processSavedata($valid);
		$this->assertSame(['widget_width', 'is_last'], array_keys($valid->savedata));
		$this->assertSame('full', $valid->savedata['widget_width']);
		$this->assertSame('1', $valid->savedata['is_last']);

		$currentlyStillValid = $this->createForm(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_widget_invalid_shape', [
			'item_id' => (string)$fixture_id,
		]);
		$this->setInputValues($currentlyStillValid, [
			'widget_width' => '',
			'is_last' => null,
		]);
		$this->validateForm($currentlyStillValid);
		$this->assertTrue($currentlyStillValid->isValid(), 'Current widget settings form has no validators; empty settings are accepted.');
	}

	public function testAdminMenuElementValidationAndSavedataAreCharacterized(): void
	{
		$valid = $this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu_valid');
		$this->setInputValues($valid, [
			'node_name' => 'Phase 1 Menu',
			'type' => 'kulso',
			'url' => 'https://example.test/',
			'page_id' => '',
		]);
		$this->validateForm($valid);
		$this->assertTrue($valid->isValid());
		$this->processSavedata($valid);
		$this->assertSame(['node_name', 'type', 'url', 'page_id'], array_keys($valid->savedata));
		$this->assertSame('kulso', $valid->savedata['type']);
		$this->assertSame('https://example.test/', $valid->savedata['url']);

		$invalid = $this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_adminmenu_invalid');
		$this->setInputValues($invalid, [
			'node_name' => '',
			'type' => '',
			'url' => '',
			'page_id' => '',
		]);
		$this->validateForm($invalid);
		$this->assertFalse($invalid->isValid());
		$this->assertNotEmpty($invalid->getInput('node_name')?->getErrors());
		$this->assertNotEmpty($invalid->getInput('type')?->getErrors());
		$this->assertNotEmpty($invalid->getInput('url')?->getErrors());
	}

	public function testGoldenFormAclDecisionsAreCharacterizedWithPermanentFixtures(): void
	{
		$this->assertTrue($this->createForm(FormList::USERLOGIN, 'phase1_acl_login')->hasRole());

		$this->impersonate('form_phase1_users_admin');
		$this->assertTrue($this->createForm(FormList::USER, 'phase1_acl_user_allowed')->hasRole());

		$this->impersonate('form_phase1_content_admin');
		$this->assertTrue($this->createForm(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_acl_widget_allowed', [
			'item_id' => (string)$this->widgetConnectionSettingsFixtureId(),
		])->hasRole());

		$this->impersonate('form_phase1_system_developer');
		$this->assertTrue($this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_acl_adminmenu_allowed')->hasRole());

		$this->impersonate('form_phase1_denied');
		$this->assertFalse($this->createForm(FormList::USER, 'phase1_acl_user_denied')->hasRole());
		$this->assertFalse($this->createForm(FormList::WIDGETCONNECTIONSETTINGS, 'phase1_acl_widget_denied', [
			'item_id' => (string)$this->widgetConnectionSettingsFixtureId(),
		])->hasRole());
		$this->assertFalse($this->createForm(FormList::ADMINMENUMENUELEMENT, 'phase1_acl_adminmenu_denied')->hasRole());
	}

	/**
	 * @param array<string, string> $get
	 * @param array<string, mixed> $post
	 */
	private function createForm(string $formType, string $formId, array $get = [], array $post = []): AbstractForm
	{
		$this->setRequestContext($get, $post);
		$className = 'FormType' . $formType;
		$form = new $className($formType, $formId, $this->treeContext(), '/phase-1-referer');
		$this->assertInstanceOf(AbstractForm::class, $form);

		return $form;
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function setInputValues(AbstractForm $form, array $values): void
	{
		foreach ($values as $field => $value) {
			$input = $form->getInput((string)$field);
			$this->assertNotNull($input, "Missing form input: {$field}");
			$input->setValue($value);
		}
	}

	private function validateForm(AbstractForm $form): void
	{
		$method = new ReflectionMethod($form, '_validateData');
		$method->invoke($form);
	}

	private function processSavedata(AbstractForm $form): void
	{
		$method = new ReflectionMethod($form, '_processSavedata');
		$method->invoke($form);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizeFormTree(AbstractForm $form): array
	{
		$tree = $form->buildTree();
		$this->assertSame($tree['contents'], $tree['slots']);
		$this->assertSame($tree['props']['form_class'], $tree['meta']['html']['wrapper_template'] ?? null);

		return [
			'type' => $tree['type'],
			'component' => $tree['component'],
			'props' => [
				'form_id' => $tree['props']['form_id'],
				'form_instance_id' => $tree['props']['form_instance_id'],
				'form_descriptor_id' => $tree['props']['form_descriptor_id'],
				'form_name' => $tree['props']['form_name'],
				'mode' => $tree['props']['mode'],
				'action' => $tree['props']['action'],
				'method' => $tree['props']['method'],
				'autocomplete' => $tree['props']['autocomplete'],
				'focusable' => $tree['props']['focusable'],
				'post_javascript_file' => $tree['props']['post_javascript_file'],
				'button_save_class' => $tree['props']['button_save']['class'] ?? null,
				'button_cancel_class' => $tree['props']['button_cancel']['class'] ?? null,
				'field_refs' => $tree['props']['field_refs'],
				'submit_context' => $this->normalizeSubmitContext($tree['props']['submit_context']),
			],
			'hidden_fields' => array_map(
				static fn (array $input): string => (string)$input['component'],
				$tree['slots']['hidden_fields']
			),
			'rows' => array_map(fn (array $row): array => $this->normalizeRowTree($row), $tree['slots']['rows']),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizeRowTree(array $row): array
	{
		return [
			'component' => $row['component'],
			'row_id' => $row['props']['row_id'],
			'inputs' => array_map(fn (array $input): array => $this->normalizeInputTree($input), $row['slots']['content']),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizeInputTree(array $input): array
	{
		$props = $input['props'];

		return [
			'component' => $input['component'],
			'fieldname' => $props['fieldname'],
			'input_type' => $props['input_type'],
			'field_key' => $props['field_key'],
			'data_field_key' => $props['data_field_key'],
			'name' => $props['name'],
			'save' => $props['save'],
			'required' => $props['required'] ?? null,
			'validators' => array_map(
				static fn (array $validator): string => (string)$validator['validator'],
				$props['validators']
			),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expectedInputRow(
		string $inputId,
		string $component,
		string $fieldname,
		string $inputType,
		bool $save,
		?bool $required,
		array $validators
	): array {
		return [
			'component' => 'form.row',
			'row_id' => 'row_' . $inputId,
			'inputs' => [
				[
					'component' => $component,
					'fieldname' => $fieldname,
					'input_type' => $inputType,
					'field_key' => $fieldname,
					'data_field_key' => $fieldname,
					'name' => $fieldname,
					'save' => $save,
					'required' => $required,
					'validators' => $validators,
				],
			],
		];
	}

	/**
	 * @param list<string> $fieldnames
	 * @return array<string, array<string, string>>
	 */
	private function expectedFieldRefs(string $formInstanceId, array $fieldnames): array
	{
		$refs = [];
		$input_number = 0;

		foreach ($fieldnames as $fieldname) {
			$input_id = 'f' . $formInstanceId . '_input_' . ++$input_number;
			$refs[$fieldname] = [
				'id' => $input_id,
				'key' => $fieldname,
				'name' => $fieldname,
				'row_id' => 'row_' . $input_id,
			];
		}

		return $refs;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expectedSubmitContext(string $formType, string $formInstanceId, ?int $itemId = null): array
	{
		return [
			'form_id' => $formType,
			'form_instance_id' => $formInstanceId,
			'item_id' => $itemId ?? '',
			'return_target' => '/phase-1-referer',
			'host_page_id' => 1,
			'widget_connection_id' => '',
			'form_build_id' => '<build-id>',
			'form_context_params' => '',
		];
	}

	/**
	 * @param array<string, mixed> $submitContext
	 * @return array<string, mixed>
	 */
	private function normalizeSubmitContext(array $submitContext): array
	{
		$submitContext['form_build_id'] = trim((string)($submitContext['form_build_id'] ?? '')) === '' ? '' : '<build-id>';
		$submitContext['form_context_params'] = trim((string)($submitContext['form_context_params'] ?? '')) === '' ? '' : '<encoded-context>';

		return $submitContext;
	}

	/**
	 * @param array<string, mixed> $get
	 * @param array<string, mixed> $post
	 */
	private function setRequestContext(array $get = [], array $post = []): void
	{
		$currentUser = null;
		$userSessionInitialized = false;

		try {
			$ctx = RequestContextHolder::current();
			$currentUser = $ctx->currentUser;
			$userSessionInitialized = $ctx->userSessionInitialized;
		} catch (Throwable) {
		}

		$server = [
			'HTTP_HOST' => 'localhost',
			'REQUEST_URI' => '/phase-1-form-characterization',
			'REQUEST_METHOD' => $post === [] ? 'GET' : 'POST',
		];

		$_GET = $get;
		$_POST = $post;
		$_SERVER = $server;
		RequestContextHolder::initializeRequest(get: $get, post: $post, server: $server);
		$ctx = RequestContextHolder::current();
		$ctx->currentUser = $currentUser;
		$ctx->userSessionInitialized = $userSessionInitialized;
	}

	private function treeContext(): iTreeBuildContext
	{
		return new class () implements iTreeBuildContext {
			public function getPageId(): ?int
			{
				return 1;
			}

			public function getPagedata($key)
			{
				return null;
			}

			public function registerRenderedLayoutComponent(iLayoutComponent $layoutComponent): void
			{
			}

			public function getLayoutTypeName(): ?string
			{
				return 'admin_default';
			}

			public function addToTitle(string $addition): void
			{
			}

			public function isEditable(): bool
			{
				return true;
			}

			public function getTheme(): ?AbstractThemeData
			{
				return null;
			}

			public function overrideLayoutType(string $layoutTypeName): void
			{
			}
		};
	}

	private function impersonate(?string $username): void
	{
		$ctx = RequestContextHolder::current();

		if ($username === null) {
			$ctx->currentUser = null;
			$ctx->userSessionInitialized = true;
			Cache::flush(Roles::class);
			Cache::flush(User::class);

			return;
		}

		$user = EntityUser::findFirst(['username' => $username]);
		$this->assertNotNull($user, "Missing test fixture user: {$username}");

		$ctx->currentUser = $user->data();
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}

	private function widgetConnectionSettingsFixtureId(): int
	{
		$connection = DbHelper::selectOne('widget_connections', [
			'connection_id' => self::WIDGET_CONNECTION_SETTINGS_FIXTURE_ID,
		]);
		$this->assertNotNull($connection, 'Missing widget connection settings characterization fixture.');

		$settings = WidgetSettings::getSettings(self::WIDGET_CONNECTION_SETTINGS_FIXTURE_ID);
		$this->assertSame('three_fourth', $settings['widget_width'] ?? null);
		$this->assertArrayHasKey('is_last', $settings);

		return self::WIDGET_CONNECTION_SETTINGS_FIXTURE_ID;
	}

	private function adminMenuElementFixtureId(): int
	{
		$node = DbHelper::selectOne('adminmenu_tree', [
			'node_id' => self::ADMINMENU_UPDATE_FIXTURE_ID,
		]);
		$this->assertNotNull($node, 'Missing admin menu characterization fixture.');
		$this->assertSame('E2E Editable External', $node['node_name']);
		$this->assertNull($node['page_id']);
		$this->assertSame('#', $node['url']);

		return self::ADMINMENU_UPDATE_FIXTURE_ID;
	}
}
