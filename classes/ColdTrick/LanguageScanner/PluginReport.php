<?php

namespace ColdTrick\LanguageScanner;

use Elgg\Exceptions\InvalidArgumentException;
use Elgg\Project\Paths;
use FilesystemIterator;

/**
 * PluginReport
 */
class PluginReport {
	
	protected \ElggPlugin $plugin;
	
	protected array $plugin_language_keys = []; // keys in the en language file of the plugin

	protected array $plugin_files = []; // plugin files that hold potential elgg_echo's
	
	protected array $unused_language_keys = []; // language keys from the language file that appear not to be used in the plugins code
	
	protected array $system_messages = []; // language keys from plugin files that appear not to be translated
	
	protected array $code_language_keys = [];
	
	/**
	 * Create a new report
	 *
	 * @param \ElggPlugin $plugin the plugin to create a report for
	 */
	public function __construct(\ElggPlugin $plugin) {
		
		$this->loadAllPluginTranslations();
		
		$this->plugin = $plugin;
		
		$this->loadPluginLanguageKeys();
		
		$this->plugin_files = $this->getPluginFiles();
		
		$this->scanForUnusedKeys();
		
		$this->scanCodeForKeys();
	}
	
	/**
	 * Get the total number of translation keys
	 *
	 * @return int
	 */
	public function getTotalKeyCount(): int {
		return count($this->plugin_language_keys);
	}
	
	/**
	 * Get the total number of unused translation keys
	 *
	 * @return int
	 */
	public function getUnusedKeyCount(): int {
		return count($this->unused_language_keys);
	}
	
	/**
	 * Get the unused translation keys
	 *
	 * @return array
	 */
	public function getUnusedKeys(): array {
		return $this->unused_language_keys;
	}
	
	/**
	 * Get the language keys used in the code
	 *
	 * @return array
	 */
	public function getCodeLanguageKeys(): array {
		return $this->code_language_keys;
	}
	
	/**
	 * Get the system messages which are untranslatable
	 *
	 * @return array
	 */
	public function getUntranslatableSystemMessages(): array {
		return $this->system_messages;
	}
	
	/**
	 * Get the language keys which aren't present in a language file
	 *
	 * @return array
	 */
	public function getUntranslatableCodeLanguageKeys(): array {
		$result = [];
		foreach ($this->getCodeLanguageKeys() as $key) {
			if (!elgg_language_key_exists($key)) {
				$result[] = $key;
			}
		}
		
		return $result;
	}
	
	/**
	 * Get the count of the language keys used in the code
	 *
	 * @return int
	 */
	public function countCodeLanguageKeys(): int {
		return count($this->code_language_keys);
	}
	
	/**
	 * Returns array of suggestions of plugin keys that could probably be replaced with core keys
	 *
	 * @return array
	 */
	public function getSuggestions(): array {
		$suggestions = [];
		$plugin_translations = $this->plugin_language_keys;
		$plugin_translations = array_map('strtolower', $plugin_translations);
		
		$core_translations = include(Paths::elgg() . 'languages/en.php');
		$core_translations = array_map('strtolower', $core_translations);
		
		foreach ($plugin_translations as $plugin_key => $plugin_value) {
			foreach ($core_translations as $core_key => $core_value) {
				similar_text($plugin_value, $core_value, $similarity);
				if ($similarity > 90) {
					$suggestions[$plugin_key] = $core_key;
					break;
				}
			}
		}
		
		return $suggestions;
	}
	
	/**
	 * Load translations of all plugins
	 *
	 * @return void
	 */
	protected function loadAllPluginTranslations(): void {
		$plugins = elgg_get_plugins('all');
		foreach ($plugins as $plugin) {
			$path = elgg_get_plugins_path() . $plugin->getID() . '/languages';
			if (is_dir($path)) {
				elgg()->translator->registerTranslations($path);
			}
		}
	}
	
	/**
	 * Function to scan for language keys used in the plugin code
	 *
	 * @return void
	 */
	protected function scanCodeForKeys(): void {
		if (empty($this->plugin_files)) {
			return;
		}
		
		foreach ($this->plugin_files as $file) {
			if (!file_exists($file)) {
				continue;
			}
				
			$contents = file_get_contents($file);
				
			if (empty($contents)) {
				continue;
			}
			
			// elgg_echo's
			$pattern = "/(?>->translate|elgg_echo|\.echo)\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
			preg_match_all($pattern, $contents, $matches);
			
			if (!empty($matches)) {
				$keys = elgg_extract(1, $matches);
				$this->code_language_keys = array_merge($this->code_language_keys, $keys);
			}
			
			// elgg_register_success_message
			$pattern = "/elgg_register_success_message\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
			preg_match_all($pattern, $contents, $matches);
			if (!empty($matches)) {
				$keys = elgg_extract(1, $matches);
				$this->system_messages = array_merge($this->system_messages, $keys);
			}
			
			// elgg_register_error_message
			$pattern = "/elgg_register_error_message\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
			preg_match_all($pattern, $contents, $matches);
			if (!empty($matches)) {
				$keys = elgg_extract(1, $matches);
				$this->system_messages = array_merge($this->system_messages, $keys);
			}
		}
		
		$this->code_language_keys = array_unique($this->code_language_keys);
		$this->system_messages = array_unique($this->system_messages);
	}
	
	/**
	 * Function to scan for unused language keys
	 *
	 * @return void
	 */
	protected function scanForUnusedKeys(): void {
		if (empty($this->plugin_files)) {
			return;
		}

		if (empty($this->plugin_language_keys)) {
			return;
		}
		
		$this->unused_language_keys = $this->plugin_language_keys;
		
		foreach ($this->plugin_files as $file) {
			if (!file_exists($file)) {
				continue;
			}
			
			$contents = file_get_contents($file);
			if (empty($contents)) {
				continue;
			}
			
			foreach ($this->unused_language_keys as $key => $value) {
				if ($key === $this->plugin->getID()) {
					unset($this->unused_language_keys[$key]);
					continue;
				}
				
				if (substr($key, 0, 6) == 'admin:') {
					unset($this->unused_language_keys[$key]);
					continue;
				}
				
				$pattern = "/(?>->translate|elgg_echo|\.echo)\(\\\{0,1}['\"]{$key}\\\{0,1}['\"]/i";
				if (preg_match($pattern, $contents)) {
					unset($this->unused_language_keys[$key]);
				}
			}
			
			if (empty($this->unused_language_keys)) {
				break;
			}
		}
		
		// strip allowed keys
		$this->unused_language_keys = array_filter($this->unused_language_keys, function($key) {
			$patterns = [
				'^(item|collection):object:(\w*)',
				'^entity:delete:(\w*)',
				'^widgets:(\w*):(name|description)$',
				"^{$this->plugin->getId()}:upgrade:(\d+):(title|description)$",
			];
			
			$patterns = implode('|', $patterns);
			if (preg_match("/{$patterns}/", $key)) {
				return false;
			}
			
			return true;
		}, ARRAY_FILTER_USE_KEY);
	}
	
	/**
	 * Returns array of all plugin files
	 *
	 * @param array $valid_extensions array of extensions of files that will be returned
	 *
	 * @return \SplFileInfo[]
	 */
	protected function getPluginFiles(array $valid_extensions = ['php', 'html', 'js']): array {
		$skip_folders = ['.git', 'vendor', 'vendors', '.svn', 'tests', 'languages'];
		
		$files = [];
		
		$base_path = \Elgg\Project\Paths::sanitize(elgg_get_plugins_path() . $this->plugin->getID());
		$directory = new \RecursiveDirectoryIterator($base_path, \FilesystemIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directory);
		foreach ($iterator as $file) {
			$file_folder = \Elgg\Project\Paths::sanitize($file->getPath());
			$file_folder = str_replace($base_path, '', $file_folder);
			foreach ($skip_folders as $skip) {
				if (str_starts_with($file_folder, $skip)) {
					continue(2);
				}
			}
			
			if (!in_array($file->getExtension(), $valid_extensions)) {
				continue;
			}
			
			$files[] = $file;
		}
		
		return $files;
	}
	
	/**
	 * Returns array with all language keys from a language files
	 *
	 * @return void
	 */
	protected function loadPluginLanguageKeys(): void {
		$language_file = elgg_get_plugins_path() . $this->plugin->getID() . '/languages/en.php';
		if (!file_exists($language_file)) {
			return;
		}
		
		$contents = file_get_contents($language_file);
		if (empty($contents)) {
			return;
		}
		
		$ln = include($language_file);
		if (!is_array($ln)) {
			return;
		}
		
		// language files using return array() formatting
		$this->plugin_language_keys = $ln;
		
		// remove optional blank
		unset($this->plugin_language_keys['']);
		
		// always add them, as we can also scan disabled plugins
		elgg()->translator->addTranslation('en', $this->plugin_language_keys);
	}
}
