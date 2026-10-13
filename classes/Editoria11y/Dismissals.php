<?php

namespace Medienbaecker\Editoria11y;

use Kirby\Cms\User;

class Dismissals
{
	/**
	 * "OK" applies to everyone, "hide" only to the user who hid it
	 */
	public static function forPage(string $page, User $user): array
	{
		$rows = Storage::database()->query(
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
		$database = Storage::database();

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
}
