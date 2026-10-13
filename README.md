# Kirby Editoria11y

Make accessibility issues visible to editors in the Kirby Panel, powered by [Editoria11y](https://editoria11y.com).

## Installation

```
composer require medienbaecker/kirby-editoria11y
```

Or download this repository and put it in `site/plugins/kirby-editoria11y`.

Requires Kirby 5. No configuration needed. The checker is automatically set to the logged-in account's Panel language.

## How it works

Opening a page's preview runs the checker. Editors see what to fix. Each alert has two buttons: "Mark OK" hides it for all editors, "Ignore" only for the editor who clicked it.

![The Panel preview of "About us" with Editoria11y markers on the text and an open alert on an image: "Invalid HTML: image has no alt attribute", with advice on how to fix it.](.github/preview.png)

The preview button shows how many alerts a page had at its last check:

![The header of the "About us" page in the Panel. The preview button has a red badge with a 4.](.github/preview-button.png)

The Accessibility area lists all pages and can check them all at once:

![The Accessibility area in the Kirby Panel: a stats section saying "6 pages with alerts", "9 of 9 pages checked", "2 errors" and "8 warnings". Below, a table lists each page with when it was last checked and its number of errors and warnings, starting with "About us" at 1 error and 3 warnings.](.github/overview.png)

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
],
```

`pages` takes a function that returns a pages collection. The example reuses a `sitemap` collection the site already has, which leaves out pages that aren't real pages, like a test bed or sections that only exist as anchors on another page.

## License

GPL-2.0. Bundles Editoria11y 3.0.2 ([source](https://github.com/itmaybejj/editoria11y/tree/3.0.2)).
