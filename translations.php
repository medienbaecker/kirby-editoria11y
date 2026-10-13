<?php

$translations = [];

foreach (glob(__DIR__ . '/translations/*.php') ?: [] as $file) {
	$translations[pathinfo($file, PATHINFO_FILENAME)] = require $file;
}

return $translations;
