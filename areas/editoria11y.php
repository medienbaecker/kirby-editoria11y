<?php

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Toolkit\I18n;
use Medienbaecker\Editoria11y\Checker;
use Medienbaecker\Editoria11y\Results;
use Medienbaecker\Editoria11y\Storage;

$previewButton = function (Page|null $page, string $panelUrl): array|null {
	if ($page === null || $page->previewUrl() === null) {
		return null;
	}

	$button = [
		'class' => 'k-preview-view-button',
		'icon'  => 'window',
		'link'  => $panelUrl . '/preview/form',
		'title' => I18n::translate('preview'),
	];

	$user = kirby()->user();

	if ($user === null || Checker::isEnabled($page, $user) === false) {
		return $button;
	}

	$button = [...$button, ...Results::button($page, $user)];

	try {
		$outdated = Results::isOutdated($page);
	} catch (Throwable) {
		return $button;
	}

	if ($outdated === false) {
		return $button;
	}

	$language = kirby()->language()?->code();

	return [
		'component' => 'k-editoria11y-preview-button',
		...$button,
		'check' => [
			'id'       => Storage::pageKey($page, $language),
			'page'     => $page->uuid()?->toString() ?? $page->id(),
			'language' => $language,
			'url'      => $page->url($language),
		],
	];
};

return [
	'editoria11y' => fn () => [
		'label' => t('medienbaecker.editoria11y.title'),
		'icon'  => 'accessibility',
		'menu'  => true,
		'link'  => 'editoria11y',
		'views' => [
			[
				'pattern' => 'editoria11y',
				'action'  => function () {
					try {
						$rows = Results::rows(kirby()->user());
					} catch (Throwable) {
						$rows = [];
					}

					return [
						'component' => 'k-editoria11y-view',
						'title'     => I18n::translate('medienbaecker.editoria11y.title'),
						'props'     => [
							'rows' => $rows,
						],
					];
				},
			],
		],
		'buttons' => [
			'page.preview' => fn (Page $page) => $previewButton($page, $page->panel()->url(true)),
			'site.preview' => fn (Site $site) => $previewButton($site->homePage(), $site->panel()->url(true)),
		],
	],
];
