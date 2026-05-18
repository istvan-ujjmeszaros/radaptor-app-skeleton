<?php

/**
 * Fixture for the adminmenu_tree table.
 *
 * High node IDs are reserved here for form characterization rows.
 */
class FixtureAdminmenuTree extends AbstractFixture
{
	public function getTableName(): string
	{
		return 'adminmenu_tree';
	}

	/**
	 * @return list<class-string<AbstractFixture>>
	 */
	public function getDependencies(): array
	{
		return [];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function getData(): array
	{
		return [
			[
				'node_id' => 9002,
				'node_name' => 'E2E Editable External',
				'node_type' => 'submenu',
				'page_id' => null,
				'url' => '#',
				'_' => [],
			],
		];
	}
}
