<?php

namespace Medienbaecker\Editoria11y;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Tpl;
use Throwable;

class Checker
{
	public static function inject(string $html, Page $page): string
	{
		$position = strripos($html, '</body>');

		if ($position === false || str_contains($html, 'id="kirby-editoria11y"') === true) {
			return $html;
		}

		$markup = static::markup($page);

		if ($markup === '') {
			return $html;
		}

		return substr_replace($html, $markup, $position, 0);
	}

	public static function markup(Page $page): string
	{
		$kirby = $page->kirby();

		// Keeps responses for logged-in users out of the page cache, so the
		// markup can never be served to visitors from there.
		$kirby->response()->usesCookie($kirby->option('session.cookieName', 'kirby_session'));

		$user = $kirby->user();

		if ($user === null || static::isEnabled($page, $user) === false) {
			return '';
		}

		$plugin   = $kirby->plugin('medienbaecker/editoria11y');
		$language = static::language($user);

		return Tpl::load(__DIR__ . '/../../views/markup.php', [
			'config'  => json_encode(static::config($page, $user, $language), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
			'scripts' => [
				$plugin->asset('editoria11y/lang/' . $language . '.umd.js')->url(),
				$plugin->asset('editoria11y/ed11y.umd.min.js')->url(),
				$plugin->asset('kirby-editoria11y.js')->url(),
			],
			'nonce' => $kirby->nonce(),
		]);
	}

	public static function isEnabled(Page $page, User $user): bool
	{
		$enabled = option('medienbaecker.editoria11y.enabled');

		if ($enabled instanceof Closure) {
			return $enabled($page, $user) === true;
		}

		return $enabled === true;
	}

	public static function config(Page $page, User $user, string $language): array
	{
		$kirby  = $page->kirby();
		$config = option('medienbaecker.editoria11y.config', []);

		if ($config instanceof Closure) {
			$config = $config($page);
		}

		$data = [
			'options' => [
				'pepper' => 'kirby-editoria11y',
				...$config,
			],
			'lang' => 'Ed11yLang' . ucfirst($language),
			'css'  => $kirby->plugin('medienbaecker/editoria11y')->asset('editoria11y/editoria11y.min.css')->url(),
		];

		if (option('medienbaecker.editoria11y.sync') === true) {
			$contentLanguage = $kirby->language()?->code();
			$key             = Dismissals::pageKey($page, $contentLanguage);

			try {
				$data['sync'] = [
					'key'        => $key,
					'page'       => $page->uuid()?->toString() ?? $page->id(),
					'language'   => $contentLanguage,
					'api'        => $kirby->url('api') . '/editoria11y/dismissals',
					'csrf'       => $kirby->csrf(),
					'dismissals' => (object)Dismissals::forPage($key, $user),
				];
			} catch (Throwable) {
				// Without a working database, dismissals stay in the browser
			}
		}

		return $data;
	}

	public static function language(User $user): string
	{
		$code = str_replace('_', '-', strtolower($user->language()));
		$root = App::instance()->plugin('medienbaecker/editoria11y')->root() . '/assets/editoria11y/lang/';

		foreach ([$code, explode('-', $code)[0]] as $candidate) {
			if (
				preg_match('/^[a-z-]+$/', $candidate) === 1 &&
				F::exists($root . $candidate . '.umd.js') === true
			) {
				return $candidate;
			}
		}

		return 'en';
	}
}
