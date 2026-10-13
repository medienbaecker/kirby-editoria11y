<?php

namespace Medienbaecker\Editoria11y;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Filesystem\F;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Tpl;
use Throwable;

class Checker
{
	public static function inject(string $html, Page $page): string
	{
		$position = strripos($html, '</body>');

		if ($position === false) {
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
		$scan  = $kirby->request()->get('editoria11y') === 'scan';

		if ($scan === false && $kirby->request()->get('_preview') !== 'true') {
			return '';
		}

		$user = $kirby->user();

		if ($user === null || static::isEnabled($page, $user) === false) {
			return '';
		}

		$plugin   = $kirby->plugin('medienbaecker/editoria11y');
		$language = static::language($user);

		// The checker reads attributes and styles, never the files themselves
		if ($scan === true) {
			$kirby->response()->header('Content-Security-Policy', "img-src data:; media-src 'none'; font-src 'none'; frame-src 'none'");
		}

		return Tpl::load(__DIR__ . '/../../views/markup.php', [
			'config'  => json_encode([...static::config($page, $user, $language), 'scan' => $scan], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
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
				'allowHide' => false,
			],
			'lang' => 'Ed11yLang' . ucfirst($language),
			'strings' => [
				'WARNING'  => I18n::translate('medienbaecker.editoria11y.warning', null, $user->language()),
				'WARNINGS' => I18n::translate('medienbaecker.editoria11y.warnings', null, $user->language()),
			],
			'css'  => $kirby->plugin('medienbaecker/editoria11y')->asset('editoria11y/editoria11y.min.css')->url(),
		];

		$contentLanguage = $kirby->language()?->code();
		$key             = Storage::pageKey($page, $contentLanguage);

		try {
			$data['sync'] = [
				'key'        => $key,
				'page'       => $page->uuid()?->toString() ?? $page->id(),
				'language'   => $contentLanguage,
				'api'        => $kirby->url('api') . '/editoria11y',
				'report'     => static::showsPublishedContent($page, $contentLanguage),
				'csrf'       => $kirby->csrf(),
				'dismissals' => (object)Dismissals::forPage($key),
			];
		} catch (Throwable) {
			// Without a working database, dismissals stay in the browser
		}

		return $data;
	}

	/**
	 * Results of unsaved changes must not overwrite those of the published page
	 */
	protected static function showsPublishedContent(Page $page, string|null $language): bool
	{
		if ($page->kirby()->request()->get('_version') !== 'changes') {
			return true;
		}

		$changes  = $page->version('changes');
		$language ??= 'default';

		return $changes->exists($language) === false || $changes->isIdentical('latest', $language);
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
