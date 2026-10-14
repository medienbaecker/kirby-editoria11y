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

![The header of the "About us" page in the Panel. The preview button has a red badge with a 4.](.github/badge.png)

In the page preview, editors see what to fix. Errors stay until they're fixed. Warnings can be marked OK, which hides them for all editors.

![The Panel preview of "About us" with Editoria11y markers on the text and an open alert on an image: "Invalid HTML: image has no alt attribute", with advice on how to fix it.](.github/preview-alert.png)

In the Accessibility view you can check all pages at once:

https://github.com/user-attachments/assets/69380f9f-a5f5-4aea-b545-204072a4196b

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

GPL-2.0, because it bundles Editoria11y 3.0.2 ([source](https://github.com/itmaybejj/editoria11y/tree/3.0.2)), which is GPL-2.0 and includes parts of [Sa11y](https://github.com/ryersondmp/sa11y) under the MIT License. Copyright (c) 2026 Thomas Günther.
