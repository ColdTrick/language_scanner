<?php

namespace ColdTrick\LanguageScanner;

use Elgg\Exceptions\InvalidArgumentException;
use Elgg\Project\Paths;
use FilesystemIterator;

/**
 * Plugin Report
 */
class PluginReport extends LanguageReport {
	
	/**
	 * Create a new report
	 *
	 * @param \ElggPlugin $plugin the plugin to create a report for
	 */
	public function __construct(protected \ElggPlugin $plugin) {
		parent::__construct();
		
		$this->root_path = $plugin->getPath();
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function filterAllowedUnusedKeys(array $unused_language_keys): array {
		$result = parent::filterAllowedUnusedKeys($unused_language_keys);
		
		return array_filter($result, function($key) {
			$patterns = [
				"^groups:tool:{$this->plugin->getID()}(?::description)?$",
			];
			
			$patterns = implode('|', $patterns);
			if (preg_match("/{$patterns}/", $key)) {
				return false;
			}
			
			return true;
		}, ARRAY_FILTER_USE_KEY);
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function loadLanguageKeys(): void {
		parent::loadLanguageKeys();
		
		unset($this->language_keys[$this->plugin->getID()]);
	}
}
