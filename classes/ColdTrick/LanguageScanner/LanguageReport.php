<?php

namespace ColdTrick\LanguageScanner;

use Elgg\Exceptions\BadMethodCallException;
use Elgg\Project\Paths;

/**
 * Language report
 */
class LanguageReport {
	
	protected array $language_keys = []; // keys in the en language file

	protected array $files = []; // files that hold potential elgg_echo's
	
	protected array $unused_language_keys = []; // language keys from the language file that appear not to be used in the code
	
	protected array $system_messages = []; // language keys from files that appear not to be translated (used in system/error messages)
	
	protected array $code_language_keys = [];
	
	protected string $root_path = '';
	
	/**
	 * Create a new report
	 */
	public function __construct() {
		$this->root_path = Paths::elgg();
	}
	
	/**
	 * Generate the language report
	 *
	 * @return void
	 */
	public function generateReport(): void {
		if (empty($this->root_path)) {
			throw new BadMethodCallException('make sure $root_path is set in ' . __CLASS__);
		}
		
		set_time_limit(0);
		
		$this->loadAllPluginTranslations();
		
		$this->loadLanguageKeys();
		
		$this->files = $this->getFiles();
		
		$this->scanForUnusedKeys();
		
		$this->scanCodeForKeys();
	}
	
	/**
	 * Get the total number of translation keys
	 *
	 * @return int
	 */
	public function getTotalKeyCount(): int {
		return count($this->language_keys);
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
		$translations = $this->language_keys;
		$translations = array_map('strtolower', $translations);
		
		$core_translations = include(Paths::elgg() . 'languages/en.php');
		$core_translations = array_map('strtolower', $core_translations);
		
		foreach ($translations as $key => $value) {
			foreach ($core_translations as $core_key => $core_value) {
				similar_text($value, $core_value, $similarity);
				if ($similarity > 90) {
					$suggestions[$key] = $core_key;
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
		if (empty($this->files)) {
			return;
		}
		
		foreach ($this->files as $file) {
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
		if (empty($this->files)) {
			return;
		}

		if (empty($this->language_keys)) {
			return;
		}
		
		$unused_language_keys = $this->language_keys;
		
		foreach ($this->files as $file) {
			if (!file_exists($file)) {
				continue;
			}
			
			$contents = file_get_contents($file);
			if (empty($contents)) {
				continue;
			}
			
			foreach ($unused_language_keys as $key => $value) {
				$pattern = "/(?>->translate|elgg_echo|\.echo)\(\\\{0,1}['\"]{$key}\\\{0,1}['\"]/i";
				if (preg_match($pattern, $contents)) {
					unset($unused_language_keys[$key]);
				}
			}
			
			if (empty($unused_language_keys)) {
				break;
			}
		}
		
		// strip allowed keys
		$this->unused_language_keys = $this->filterAllowedUnusedKeys($unused_language_keys);
	}
	
	/**
	 * Filter out common unused language keys
	 *
	 * @param array $unused_language_keys current list of unused keys
	 *
	 * @return array
	 */
	protected function filterAllowedUnusedKeys(array $unused_language_keys): array {
		return array_filter($unused_language_keys, function($key) {
			$patterns = [
				'^(item|collection):(object|user|group|site):(\w*)',
				'^entity:delete:(\w*)',
				'^river:(object|user|group|site):(\w*):(\w*)',
				'^widgets:(\w*):(name|description)$',
				'^(\w*):upgrade:(\d+):(title|description)$',
			];
			
			$patterns = implode('|', $patterns);
			if (preg_match("/{$patterns}/", $key)) {
				return false;
			}
			
			return true;
		}, ARRAY_FILTER_USE_KEY);
	}
	
	/**
	 * Returns array of all files
	 *
	 * @param array $valid_extensions array of extensions of files that will be returned
	 *
	 * @return \SplFileInfo[]
	 */
	protected function getFiles(array $valid_extensions = ['php', 'html', 'js']): array {
		$skip_folders = [
			'.git',
			'.scripts',
			'.svn',
			'.tx',
			'bower_components',
			'docs',
			'engine/tests',
			'languages',
			'mod',
			'node_modules',
			'tests',
			'vendor',
			'vendors',
		];
		
		$files = [];
		
		$base_path = \Elgg\Project\Paths::sanitize($this->root_path);
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
	 * Load the EN language file
	 *
	 * @return void
	 */
	protected function loadLanguageKeys(): void {
		$language_file = Paths::sanitize($this->root_path . 'languages/en.php', false);
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
		
		$ln = array_filter($ln, function($value, $key) {
			return !empty($value) && !str_starts_with($key, 'admin:');
		}, ARRAY_FILTER_USE_BOTH);
		
		// language files using return array() formatting
		$this->language_keys = $ln;
		
		// always add them, as we can also scan disabled plugins
		elgg()->translator->addTranslation('en', $this->language_keys);
	}
}
