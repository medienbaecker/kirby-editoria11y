<?php

namespace Medienbaecker\Editoria11y;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\User;

class Results
{
	public static function save(string $page, int $errors, int $warnings, array $tests): bool
	{
		return Storage::database()->execute(
			'INSERT OR REPLACE INTO results (page, errors, warnings, tests, checked) VALUES (?, ?, ?, ?, ?)',
			[$page, $errors, $warnings, json_encode($tests), date('c')]
		);
	}

	public static function all(): array
	{
		$rows = Storage::database()->query(
			'SELECT page, errors, warnings, tests, checked FROM results',
			[],
			['fetch' => 'array', 'iterator' => 'array']
		);

		$results = [];

		foreach ($rows ?: [] as $row) {
			$results[$row['page']] = [
				'errors'   => (int)$row['errors'],
				'warnings' => (int)$row['warnings'],
				'tests'    => json_decode($row['tests'], true) ?? [],
				'checked'  => $row['checked'],
			];
		}

		return $results;
	}

	public static function forPage(Page $page): array|null
	{
		return static::all()[Storage::pageKey($page, App::instance()->language()?->code())] ?? null;
	}

	public static function rows(User $user): array
	{
		$kirby    = App::instance();
		$language = $kirby->language()?->code();
		$results  = static::all();
		$rows     = [];

		foreach (option('medienbaecker.editoria11y.pages')() as $page) {
			if (Checker::isEnabled($page, $user) === false) {
				continue;
			}

			$key    = Storage::pageKey($page, $language);
			$result = $results[$key] ?? null;

			$rows[] = [
				'id'       => $key,
				'title'    => $page->title()->value(),
				'errors'   => $result['errors'] ?? null,
				'warnings' => $result['warnings'] ?? null,
				'checked'  => $result['checked'] ?? null,
				'link'     => $page->panel()->url(true),
				'url'      => $page->url($language),
			];
		}

		return $rows;
	}

}
