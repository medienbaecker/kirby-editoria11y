<?php

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\NotFoundException;
use Kirby\Exception\PermissionException;
use Medienbaecker\Editoria11y\Checker;
use Medienbaecker\Editoria11y\Dismissals;
use Medienbaecker\Editoria11y\Results;
use Medienbaecker\Editoria11y\Storage;

$pageKey = function (App $kirby, User $user, array $input): string {
	$page = $kirby->page($input['page'] ?? '');

	if (!$page instanceof Page) {
		throw new NotFoundException('The page could not be found');
	}

	if (Checker::isEnabled($page, $user) === false) {
		throw new PermissionException('You are not allowed to check this page');
	}

	$language = $input['language'] ?? null;

	if ($language !== null && $kirby->language($language) === null) {
		throw new InvalidArgumentException('Invalid language');
	}

	return Storage::pageKey($page, $language);
};

return [
	[
		'pattern' => 'editoria11y/button',
		'method'  => 'GET',
		'action'  => function () {
			$page = $this->kirby()->page($this->requestQuery('page', ''));

			if (!$page instanceof Page || Checker::isEnabled($page, $this->user()) === false) {
				throw new NotFoundException('The page could not be found');
			}

			return Results::button($page, $this->user());
		},
	],
	[
		'pattern' => 'editoria11y/dismissals',
		'method'  => 'POST',
		'action'  => function () use ($pageKey) {
			$input  = $this->kirby()->request()->data();
			$key    = $pageKey($this->kirby(), $this->user(), $input);
			$test   = $input['test'] ?? '';
			$id     = $input['key'] ?? '';
			$action = $input['action'] ?? '';

			if (
				preg_match('/^[A-Z0-9_]{1,64}$/', $test) !== 1 ||
				preg_match('/^[\w-]{1,128}$/', $id) !== 1 ||
				in_array($action, ['ok', 'hide', 'reset'], true) === false
			) {
				throw new InvalidArgumentException('Invalid dismissal');
			}

			Dismissals::update($key, $test, $id, $action, $this->user());

			return ['status' => 'ok'];
		},
	],
	[
		'pattern' => 'editoria11y/results',
		'method'  => 'POST',
		'action'  => function () use ($pageKey) {
			$input    = $this->kirby()->request()->data();
			$key      = $pageKey($this->kirby(), $this->user(), $input);

			if (($input['skipped'] ?? false) === true) {
				Results::save($key, 0, 0, [], true);

				return ['status' => 'ok'];
			}

			$errors   = $input['errors'] ?? null;
			$warnings = $input['warnings'] ?? null;
			$tests    = $input['tests'] ?? [];

			if (
				is_int($errors) === false || $errors < 0 ||
				is_int($warnings) === false || $warnings < 0 ||
				is_array($tests) === false
			) {
				throw new InvalidArgumentException('Invalid results');
			}

			foreach ($tests as $test => $count) {
				if (preg_match('/^[A-Z0-9_]{1,64}$/', (string)$test) !== 1 || is_int($count) === false || $count < 1) {
					throw new InvalidArgumentException('Invalid results');
				}
			}

			Results::save($key, $errors, $warnings, $tests);

			return ['status' => 'ok'];
		},
	],
];
