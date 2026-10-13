<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\NotFoundException;
use Kirby\Exception\PermissionException;
use Medienbaecker\Editoria11y\Checker;
use Medienbaecker\Editoria11y\Dismissals;

require_once __DIR__ . '/classes/Editoria11y/Checker.php';
require_once __DIR__ . '/classes/Editoria11y/Dismissals.php';

Kirby::plugin('medienbaecker/editoria11y', [
	'options' => [
		'enabled'    => fn (Page $page, User $user) =>
			$user->role()->permissions()->for('access', 'panel') &&
			$page->permissions()->can('update'),
		'config'     => [],
		'autoInject' => true,
		'sync'       => true,
		'database'   => fn () => kirby()->root('logs') . '/editoria11y/dismissals.sqlite',
	],
	'snippets' => [
		'editoria11y' => __DIR__ . '/snippets/editoria11y.php',
	],
	'hooks' => [
		'page.render:after' => function (string $contentType, array $data, string $html, Page $page) {
			if ($contentType !== 'html' || option('medienbaecker.editoria11y.autoInject') !== true) {
				return $html;
			}

			return Checker::inject($html, $page);
		},
		'page.delete:after' => function (bool $status, Page $page) {
			if (option('medienbaecker.editoria11y.sync') === true) {
				Dismissals::deletePage($page);
			}
		},
	],
	'api' => [
		'routes' => [
			[
				'pattern' => 'editoria11y/dismissals',
				'method'  => 'POST',
				'action'  => function () {
					$kirby = $this->kirby();
					$input = $kirby->request()->data();
					$page  = $kirby->page($input['page'] ?? '');

					if ($page === null) {
						throw new NotFoundException('The page could not be found');
					}

					if (Checker::isEnabled($page, $this->user()) === false) {
						throw new PermissionException('You are not allowed to dismiss alerts on this page');
					}

					$language = $input['language'] ?? null;
					$test     = $input['test'] ?? '';
					$key      = $input['key'] ?? '';
					$action   = $input['action'] ?? '';

					if (
						($language !== null && $kirby->language($language) === null) ||
						preg_match('/^[A-Z0-9_]{1,64}$/', $test) !== 1 ||
						preg_match('/^[\w-]{1,128}$/', $key) !== 1 ||
						in_array($action, ['ok', 'hide', 'reset'], true) === false
					) {
						throw new InvalidArgumentException('Invalid dismissal');
					}

					Dismissals::update(Dismissals::pageKey($page, $language), $test, $key, $action, $this->user());

					return ['status' => 'ok'];
				},
			],
		],
	],
]);
