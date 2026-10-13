<?php

namespace Medienbaecker\Editoria11y;

use Closure;
use Kirby\Cms\Page;
use Kirby\Database\Database;
use Kirby\Filesystem\Dir;

class Storage
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
		$version = $version[0]['user_version'] ?? 0;

		if ($version < 1) {
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
		}

		if ($version < 2) {
			$database->execute(
				'CREATE TABLE IF NOT EXISTS results (
					page TEXT PRIMARY KEY,
					errors INTEGER NOT NULL,
					warnings INTEGER NOT NULL,
					tests TEXT NOT NULL,
					checked TEXT NOT NULL
				)'
			);
			$database->execute('PRAGMA user_version = 2');
		}

		return static::$database = $database;
	}

	public static function pageKey(Page $page, string|null $language = null): string
	{
		$key = $page->uuid()?->toString() ?? $page->id();

		return $language === null ? $key : $key . '@' . $language;
	}

	public static function deletePage(Page $page): void
	{
		$key = static::pageKey($page);

		foreach (['dismissals', 'results'] as $table) {
			static::database()->execute(
				'DELETE FROM ' . $table . ' WHERE page = ? OR substr(page, 1, ?) = ?',
				[$key, strlen($key) + 1, $key . '@']
			);
		}
	}
}
