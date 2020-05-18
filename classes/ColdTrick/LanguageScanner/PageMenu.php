<?php

namespace ColdTrick\LanguageScanner;

use Elgg\Menu\MenuItems;

class PageMenu {
	
	/**
	 * Add a menu item to the admin page menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:page'
	 *
	 * @return void|MenuItems
	 */
	public static function registerAdmin(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || !elgg_in_context('admin')) {
			return;
		}
		
		/* @var $result MenuItems */
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'administer_utilities:language_scanner',
			'text' => elgg_echo('admin:administer_utilities:language_scanner'),
			'href' => 'admin/administer_utilities/language_scanner',
			'parent_name' => 'administer_utilities',
			'section' => 'administer',
		]);
		
		return $result;
	}
}
