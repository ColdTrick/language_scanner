<?php

use ColdTrick\LanguageScanner\Bootstrap;

return [
	'plugin' => [
		'version' => '1.2',
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
