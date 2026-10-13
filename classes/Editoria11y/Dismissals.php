<?php

namespace Medienbaecker\Editoria11y;

use Kirby\Cms\User;

class Dismissals
{
	public static function forPage(string $page): array
	{
		$rows = Storage::database()->query(
			'SELECT test, key FROM dismissals WHERE page = ? AND status = ?',
			[$page, 'ok'],
			['fetch' => 'array', 'iterator' => 'array']
		);

		$dismissals = [];

		foreach ($rows ?: [] as $row) {
			$dismissals[$row['test']][$row['key']] = 'ok';
		}

		return $dismissals;
	}

	public static function update(string $page, string $test, string $key, string $action, User $user): bool
	{
		$database = Storage::database();

		if ($action === 'reset') {
			return $database->execute(
				'DELETE FROM dismissals WHERE page = ? AND test = ? AND key = ?',
				[$page, $test, $key]
			);
		}

		return $database->execute(
			'INSERT OR IGNORE INTO dismissals (page, test, key, status, user, created) VALUES (?, ?, ?, ?, ?, ?)',
			[$page, $test, $key, 'ok', $user->id(), date('c')]
		);
	}
}
