<?php

namespace ColdTrick\LanguageScanner\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add menu items to the admin_header menu
 */
class AdminHeader {
	
	/**
	 * Add a menu item to the admin header menu
	 *
	 * @param \Elgg\Event $event 'register', 'menu:admin_header'
	 *
	 * @return null|MenuItems
	 */
	public static function register(\Elgg\Event $event): ?MenuItems {
		if (!elgg_is_admin_logged_in() || !elgg_in_context('admin')) {
			return null;
		}
		
		/* @var $result MenuItems */
		$result = $event->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'administer_utilities:language_scanner',
			'text' => elgg_echo('admin:administer_utilities:language_scanner'),
			'href' => 'admin/administer_utilities/language_scanner',
			'parent_name' => 'administer_utilities',
		]);
		
		return $result;
	}
}
