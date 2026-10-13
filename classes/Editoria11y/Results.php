<?php

namespace Medienbaecker\Editoria11y;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Toolkit\I18n;
use Throwable;

class Results
{
	public static function save(string $page, int $errors, int $warnings, array $tests, bool $skipped = false): bool
	{
		return Storage::database()->execute(
			'INSERT OR REPLACE INTO results (page, errors, warnings, tests, checked, skipped) VALUES (?, ?, ?, ?, ?, ?)',
			[$page, $errors, $warnings, json_encode($tests), date('c'), (int)$skipped]
		);
	}

	public static function all(): array
	{
		$rows = Storage::database()->query(
			'SELECT page, errors, warnings, tests, checked, skipped FROM results',
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
				'skipped'  => (bool)$row['skipped'],
			];
		}

		return $results;
	}

	public static function find(string $page): array|null
	{
		$rows = Storage::database()->query(
			'SELECT errors, warnings, checked, skipped FROM results WHERE page = ?',
			[$page],
			['fetch' => 'array', 'iterator' => 'array']
		);
		$row  = ($rows ?: [])[0] ?? null;

		return $row === null ? null : [...$row, 'skipped' => (bool)$row['skipped']];
	}

	public static function forPage(Page $page): array|null
	{
		$result = static::all()[Storage::pageKey($page, App::instance()->language()?->code())] ?? null;

		return $result['skipped'] ?? false ? null : $result;
	}

	public static function isOutdated(Page $page): bool
	{
		$language = App::instance()->language()?->code();
		$result   = static::all()[Storage::pageKey($page, $language)] ?? null;

		if ($result === null) {
			return true;
		}

		if ($result['skipped'] === true) {
			return false;
		}

		return $page->modified(null, null, $language) > strtotime($result['checked']);
	}

	public static function button(Page $page, User $user): array
	{
		$button = ['badge' => null, 'title' => I18n::translate('preview')];

		try {
			$result = static::forPage($page);
		} catch (Throwable) {
			return $button;
		}

		$count = ($result['errors'] ?? 0) + ($result['warnings'] ?? 0);

		if ($count === 0) {
			return $button;
		}

		return [
			'badge' => ['text' => (string)$count, 'theme' => $result['errors'] > 0 ? 'negative' : 'notice'],
			'title' => I18n::translate('preview') . ' · ' . I18n::translateCount('medienbaecker.editoria11y.button.issues', $count, $user->language()),
		];
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

			if ($result['skipped'] ?? false) {
				continue;
			}

			$rows[] = [
				'id'       => $key,
				'title'    => $page->title()->value(),
				'errors'   => $result['errors'] ?? null,
				'warnings' => $result['warnings'] ?? null,
				'checked'  => $result['checked'] ?? null,
				'link'     => $page->panel()->url(true),
				'page'     => $page->uuid()?->toString() ?? $page->id(),
				'language' => $language,
				'url'      => $page->url($language),
			];
		}

		return $rows;
	}

}
