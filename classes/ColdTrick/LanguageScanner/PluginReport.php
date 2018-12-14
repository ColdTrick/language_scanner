<?php

namespace ColdTrick\LanguageScanner;

/**
 * PluginReport
 */
class PluginReport {
	
	private $plugin;
	
	private $plugin_language_keys = []; // keys in the en language file of the plugin

	private $plugin_files = []; // plugin files that hold potential elgg_echo's
	
	private $unused_language_keys = []; // language keys from the language file that appear not to be used in the plugins code
	
	private $system_messages = []; // language keys from plugin files that appear not to be translated
	
	private $code_language_keys = [];
	
	public function __construct($plugin_guid) {
		
		$this->loadAllPluginTranslations();
		
		$this->plugin = get_entity($plugin_guid);
		
		$this->loadPluginLanguageKeys();
		
		$this->plugin_files = $this->getPluginFiles();
		
		$this->scanForUnusedKeys();
		
		$this->scanCodeForKeys();
	}
	
	public function getTotalKeyCount() {
		return count($this->plugin_language_keys);
	}
	
	public function getUnusedKeyCount() {
		return count($this->unused_language_keys);
	}
	
	public function getUnusedKeys() {
		return $this->unused_language_keys;
	}
	
	public function getCodeLanguageKeys() {
		return $this->code_language_keys;
	}
	
	public function getUntranslatableSystemMessages() {
		return $this->system_messages;
	}
	
	public function getUntranslatableCodeLanguageKeys() {
		$result = [];
		foreach ($this->getCodeLanguageKeys() as $key) {
			if (!elgg_language_key_exists($key)) {
				$result[] = $key;
			}
		}
		return $result;
	}
	public function countCodeLanguageKeys() {
		return count($this->code_language_keys);
	}
	
	/**
	 * Returns array of suggestions of plugin keys that could probably be replaced with core keys
	 */
	public function getSuggestions() {
		$suggestions = [];
		$plugin_translations = $this->plugin_language_keys;
		$plugin_translations = array_map('strtolower', $plugin_translations);
		
		$core_translations = include(elgg_get_root_path() . 'languages/en.php');
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
	private function loadAllPluginTranslations() {
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
	private function scanCodeForKeys() {
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
			$pattern = "/elgg[_.]echo\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
			preg_match_all($pattern, $contents, $matches);
			if (!empty($matches)) {
				$keys = elgg_extract(1, $matches);
				$this->code_language_keys = array_merge($this->code_language_keys, $keys);
			}
			
			// system_messages
			$pattern = "/system[_.]message\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
			preg_match_all($pattern, $contents, $matches);
			if (!empty($matches)) {
				$keys = elgg_extract(1, $matches);
				$this->system_messages = array_merge($this->system_messages, $keys);
			}
			
			// register_error
			$pattern = "/register[_.]error\(\\\{0,1}['\"]([^'\"\$]+(?<!:))\\\{0,1}['\"]/i";
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
	private function scanForUnusedKeys() {
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
				if ($key == $this->plugin->getID()) {
					unset($this->unused_language_keys[$key]);
					continue;
				}
				if (substr($key, 0, 6) == 'admin:') {
					unset($this->unused_language_keys[$key]);
					continue;
				}
				
				$pattern = "/elgg[_.]echo\(\\\{0,1}['\"]{$key}\\\{0,1}['\"]/i";
				
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
			
			if (preg_match("/{$patterns}/", $key)){
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
	private function getPluginFiles($valid_extensions = ['php', 'html', 'js']) {
		$skip_folders = ['.git', 'vendor', 'vendors', '.svn'];
		
		$files = [];
		
		$base_path = \Elgg\Project\Paths::sanitize(elgg_get_plugins_path() . $this->plugin->getID());
		$directory = new \RecursiveDirectoryIterator($base_path, \RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directory);
		foreach ($iterator as $file) {
			$file_folder = \Elgg\Project\Paths::sanitize($file->getPath());
			$file_folder = str_replace($base_path, '', $file_folder);
			foreach ($skip_folders as $skip) {
				if (strpos($file_folder, $skip) === 0) {
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
	 * @param string $plugin_name plugin id
	 *
	 * @return array|false
	 */
	private function loadPluginLanguageKeys() {
		$language_file = elgg_get_plugins_path() . $this->plugin->getID() . '/languages/en.php';

		if (!file_exists($language_file)) {
			return;
		}
		
		$contents = file_get_contents($language_file);
		if (empty($contents)) {
			return;
		}
		
		$ln = include($language_file);
		if (is_array($ln)) {
			// language files using return array() formatting
			$this->plugin_language_keys = $ln;
		} else {
			// language files using add_translation formatting
			
			$matches = $this->matchLanguageKeys($contents);
	
			foreach ($matches[0] as $match) {
				if (empty($match)) {
					continue;
				}
				
				$language_variable_name = str_replace('$', '', $match);
				$this->plugin_language_keys = ${$language_variable_name};
			}
		}
		
		// remove optional blank
		unset($this->plugin_language_keys['']);
		
		// always add them, as we can also scan disabled plugins
		add_translation('en', $this->plugin_language_keys);
	}
	
	/**
	 * Checks variable name
	 *
	 * @param string $string string
	 *
	 * @return array
	 */
	private function matchLanguageKeys($string) {
		preg_match_all('(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)', $string, $matches);

		return $matches;
	}
}