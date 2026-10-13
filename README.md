# Kirby Editoria11y

Make accessibility issues visible to editors in the Kirby Panel, powered by [Editoria11y](https://editoria11y.com).

## Installation

```
composer require medienbaecker/kirby-editoria11y
```

Or download this repository and put it in `site/plugins/kirby-editoria11y`.

Requires Kirby 5. No configuration needed. The checker is automatically set to the logged-in account's Panel language.

## How it works

The preview button automatically shows the number of alerts for the current page. Opening a page in the Panel checks it in the background:

![The header of the "About us" page in the Panel. The preview button has a red badge with a 4.](.github/preview-button.png)

In the page preview, editors see what to fix. Errors stay until they're fixed. Warnings can be marked OK, which hides them for all editors.

![The Panel preview of "About us" with Editoria11y markers on the text and an open alert on an image: "Invalid HTML: image has no alt attribute", with advice on how to fix it.](.github/preview.png)

In the Accessibility view you can check all pages at once:

![The Accessibility area in the Kirby Panel: stats saying "41/50 pages without alerts", "50/50 checked", "5 errors" and "14 warnings". Below, a table lists each page with when it was last checked and its number of errors and warnings, starting with "Aliquip aute" at 2 errors and 1 warning.](.github/overview.png)

## Storage

Check results and warnings marked OK are stored in an SQLite database, per page and language: `site/logs/editoria11y/editoria11y.sqlite` (in Kirby's logs folder). Nothing is written to your content files. When a page is deleted, its entries are deleted too.

## Options

```php
'medienbaecker.editoria11y' => [
	// Pages in the overview (default: all pages)
	'pages' => fn () => collection('sitemap'),

	// Passed to Editoria11y, e.g. to ignore alerts caused by templates
	'config' => [
		'ignoreByTest' => [
			'QA_FAKE_HEADING' => ':not(.rich-text *)',
		],
	],

	// Path to the SQLite database
	'database' => fn () => kirby()->root('logs') . '/editoria11y/editoria11y.sqlite',
],
```

The `pages` option takes a function that returns a pages collection. I tend to have a `sitemap` collection in my sites that I also use for, well, the sitemap.

## License

GPL-2.0. Bundles Editoria11y 3.0.2 ([source](https://github.com/itmaybejj/editoria11y/tree/3.0.2)).
