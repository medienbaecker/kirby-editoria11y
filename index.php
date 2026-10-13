<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Medienbaecker\Editoria11y\Checker;
use Medienbaecker\Editoria11y\Storage;

require_once __DIR__ . '/classes/Editoria11y/Checker.php';
require_once __DIR__ . '/classes/Editoria11y/Dismissals.php';
require_once __DIR__ . '/classes/Editoria11y/Results.php';
require_once __DIR__ . '/classes/Editoria11y/Storage.php';

Kirby::plugin('medienbaecker/editoria11y', [
	'options' => [
		'enabled'  => fn (Page $page, User $user) =>
			$user->role()->permissions()->for('access', 'panel') &&
			$page->permissions()->can('update'),
		'config'   => [],
		'pages'    => fn () => site()->index()->filter(
			fn (Page $page) => $page->hasMethod('isModule') === false ||
				($page->isModule() === false && $page->isModuleContainer() === false)
		),
		'database' => fn () => kirby()->root('logs') . '/editoria11y/dismissals.sqlite',
	],
	'areas'        => require __DIR__ . '/areas/editoria11y.php',
	'translations' => require __DIR__ . '/translations.php',
	'hooks' => [
		'page.render:after' => function (string $contentType, array $data, string $html, Page $page) {
			return $contentType === 'html' ? Checker::inject($html, $page) : $html;
		},
		'page.delete:after' => function (bool $status, Page $page) {
			Storage::deletePage($page);
		},
	],
	'api' => [
		'routes' => require __DIR__ . '/routes/api.php',
	],
]);
