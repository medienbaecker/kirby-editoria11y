# Kirby Editoria11y

Shows the [Editoria11y](https://editoria11y.com) accessibility checker to editors on the frontend and in the Panel preview.

```
composer require medienbaecker/kirby-editoria11y
```

## Options

```php
'medienbaecker.editoria11y' => [
	'config'   => ['checkRoot' => 'main'], // passed to Editoria11y, or fn ($page) => [...]
	'enabled'  => fn ($page, $user) => …,  // default: Panel users who may update the page
	'sync'     => true,                     // share "Mark OK" via SQLite in site/logs
	'database' => fn () => …,               // path to the SQLite file
],
```

For custom routes, add `<?php snippet('editoria11y') ?>` before `</body>`.

## License

GPL-2.0. Bundles Editoria11y 3.0.2 ([source](https://github.com/itmaybejj/editoria11y/tree/3.0.2)).
