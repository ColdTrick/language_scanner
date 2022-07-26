<?php

use ColdTrick\LanguageScanner\Bootstrap;

return [
	'plugin' => [
		'version' => '4.0',
	],
	'bootstrap' => Bootstrap::class,
	'hooks' => [
		'register' => [
			'menu:page' => [
				'ColdTrick\LanguageScanner\PageMenu::registerAdmin' => [],
			],
		],
	],
];
