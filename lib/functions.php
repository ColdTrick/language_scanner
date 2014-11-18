<?php

/**
 * Functions for Language Scanner
 */

/**
 * Function to scan a give plugin for unused language keys 
 * 
 * @param string $plugin_name plugin id
 * 
 * @return array
 */
function language_scanner_scan_language($plugin_name) {
	$result = array();
	$found_keys = array();

	if($language_keys = language_scanner_get_language_keys_from_plugin($plugin_name)) {
		$plugin_files = language_scanner_get_plugin_files($plugin_name);
				
		$result['start_count'] = count($language_keys);
		
		$i = 0;
		foreach($plugin_files as $file) {
			if($content = language_scanner_get_content_from_file($file)) {
				foreach($language_keys as $key => $value) {
					$found_key = false;
					
					if($key == $plugin_name) {
						$found_key = true;
					}
					if(substr($key, 0, 6) == 'admin:') {
						$found_key = true;
					}
					if(strpos($content, 'elgg_echo("' . $key . '"') !== false) {
						$found_key = true;
					}
					if(strpos($content, 'elgg_echo(\'' . $key . '\'') !== false) {
						$found_key = true;
					}
					if(strpos($content, 'elgg.echo(\'' . $key . '\'') !== false) {
						$found_key = true;
					}
					if(strpos($content, 'elgg.echo("' . $key . '"') !== false) {
						$found_key = true;
					}
					if(strpos($content, 'elgg.echo(\'' . $key . '\')') !== false) {
						$found_key = true;
					}
					if(strpos($content, 'elgg.echo("' . $key . '")') !== false) {
						$found_key = true;
					}
					
					if($found_key) {
						$found_keys[$key] = $value;
						unset($language_keys[$key]);
						continue;
					}
				}
			}
		}
		
		$count = count($language_keys);
	} else {
		$count = 0;
		$language_keys = 0;
	}

	$result['end_count'] = $count;
	$result['unused'] = $language_keys;

	return $result;
}

/**
 * Loads file content
 * 
 * @param string $file filename
 * 
 * @return string|boolean
 */
function language_scanner_get_content_from_file($file) {
	if(file_exists($file)) {
		if($contents = file_get_contents($file)) {
			return $contents;
		}
	}

	return false;
}

/**
 * Returns array of all plugin files
 * 
 * @param string $plugin_name plugin id
 * @param string $recursive   boolean to flag if directory should be recursively traversed
 * 
 * @return array
 */
function language_scanner_get_plugin_files($plugin_name, $recursive = true) {
	$directory = elgg_get_plugins_path() . $plugin_name;

	return language_scanner_directory_listing($directory, $recursive);
}

/**
 * Returns array of all the files in a given directory
 * 
 * @param string $directory directory location
 * @param string $recursive boolean to flag if directory should be recursively traversed
 * 
 * @return array
 */
function language_scanner_directory_listing($directory, $recursive = true) {
	$array_items = array();
	
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if (!in_array($file, array('.', '..'))) {
				if (is_dir($directory. '/' . $file))  {
					if($recursive) {
						$array_items = array_merge($array_items, language_scanner_directory_listing($directory. "/" . $file, $recursive));
					}
				} else {
					if(language_scanner_check_extension($file)) {
						$file = $directory . '/' . $file;
						$array_items[] = preg_replace("/\/\//si", "/", $file);
					}
				}
			}
	    }
	}

	return $array_items;
}

/**
 * Returns array with all language keys from a language files
 * 
 * @param string $plugin_name plugin id
 * 
 * @return array|false
 */
function language_scanner_get_language_keys_from_plugin($plugin_name) {
	$language_arrays = array();

	$plugins_path = elgg_get_plugins_path();		
	$plugin_path = $plugins_path . $plugin_name;

	$language_file = $plugin_path . '/languages/en.php';

	if(file_exists($language_file)) {
		if($contents = file_get_contents($language_file)) {
			include($language_file);

			$matches = language_scanner_check_variable_name($contents);

			foreach($matches[0] as $match) {
				if(!empty($match)) {
					$language_variable_name = str_replace('$', '', $match);
					$language_array_from_file = ${$language_variable_name};

					$language_arrays = array_merge($language_arrays, $language_array_from_file);
				}
			}

			return $language_arrays;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Checks variable name
 * 
 * @param string $string string
 * 
 * @return array
 */
function language_scanner_check_variable_name($string) {
	preg_match_all('(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)', $string, $matches);

	return $matches;
}

/**
 * Validates usable extensions of file
 * 
 * @param string $file_name file name
 * 
 * @return boolean
 */
function language_scanner_check_extension($file_name) {
	$extension_array = explode('.', $file_name);
	$extension = end($extension_array);

	if(in_array($extension, array('php', 'html', 'js'))) {
		return true;
	}

	return false;
}
