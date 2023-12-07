<?php

return [
	'plugin' => [
		'version' => '5.1',
	],
	'events' => [
		'register' => [
			'menu:admin_header' => [
				'ColdTrick\LanguageScanner\Menus\AdminHeader::register' => [],
			],
		],
	],
	'view_options' => [
		'language_scanner/report' => [
			'ajax' => true,
		],
	],
];
