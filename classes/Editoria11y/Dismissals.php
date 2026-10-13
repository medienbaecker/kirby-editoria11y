<?php

namespace Medienbaecker\Editoria11y;

use Closure;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Database\Database;
use Kirby\Filesystem\Dir;

class Dismissals
{
	protected static Database|null $database = null;

	public static function database(): Database
	{
		if (static::$database !== null) {
			return static::$database;
		}

		$file = option('medienbaecker.editoria11y.database');

		if ($file instanceof Closure) {
			$file = $file();
		}

		Dir::make(dirname($file));

		$database = new Database([
			'id'       => 'editoria11y',
			'type'     => 'sqlite',
			'database' => $file,
		]);

		$version = $database->query('PRAGMA user_version', [], ['fetch' => 'array', 'iterator' => 'array']);

		if (($version[0]['user_version'] ?? 0) < 1) {
			$database->execute(
				'CREATE TABLE IF NOT EXISTS dismissals (
					page TEXT NOT NULL,
					test TEXT NOT NULL,
					key TEXT NOT NULL,
					status TEXT NOT NULL,
					user TEXT NOT NULL,
					created TEXT NOT NULL,
					UNIQUE (page, test, key, status, user)
				)'
			);
			$database->execute('PRAGMA user_version = 1');
		}

		return static::$database = $database;
	}

	public static function pageKey(Page $page, string|null $language = null): string
	{
		$key = $page->uuid()?->toString() ?? $page->id();

		return $language === null ? $key : $key . '@' . $language;
	}

	/**
	 * "OK" applies to everyone, "hide" only to the user who hid it
	 */
	public static function forPage(string $page, User $user): array
	{
		$rows = static::database()->query(
			'SELECT test, key, status FROM dismissals WHERE page = ? AND (status = ? OR user = ?)',
			[$page, 'ok', $user->id()],
			['fetch' => 'array', 'iterator' => 'array']
		);

		$dismissals = [];

		foreach ($rows ?: [] as $row) {
			if (($dismissals[$row['test']][$row['key']] ?? null) === 'ok') {
				continue;
			}

			$dismissals[$row['test']][$row['key']] = $row['status'];
		}

		return $dismissals;
	}

	public static function update(string $page, string $test, string $key, string $action, User $user): bool
	{
		$database = static::database();

		if ($action === 'reset') {
			return $database->execute(
				'DELETE FROM dismissals WHERE page = ? AND test = ? AND key = ? AND (status = ? OR user = ?)',
				[$page, $test, $key, 'ok', $user->id()]
			);
		}

		return $database->execute(
			'INSERT OR IGNORE INTO dismissals (page, test, key, status, user, created) VALUES (?, ?, ?, ?, ?, ?)',
			[$page, $test, $key, $action, $user->id(), date('c')]
		);
	}

	public static function deletePage(Page $page): void
	{
		$key = static::pageKey($page);

		static::database()->execute(
			'DELETE FROM dismissals WHERE page = ? OR substr(page, 1, ?) = ?',
			[$key, strlen($key) + 1, $key . '@']
		);
	}
}
