<?php

use ColdTrick\LanguageScanner\Bootstrap;

return [
	'bootstrap' => Bootstrap::class,
	'hooks' => [
		'register' => [
			'menu:page' => [
				'ColdTrick\LanguageScanner\PageMenu::registerAdmin' => [],
			],
		],
	],
];
