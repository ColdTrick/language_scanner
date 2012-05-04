<?php

	function language_scanner_scan_language($plugin_name) {
		$result = array();
		$found_keys = array();

		if($language_keys = language_scanner_get_language_keys_from_plugin($plugin_name)) {
			$plugin_files = language_scanner_get_plugin_files($plugin_name);			
			$result['start_count'] = count($language_keys);
			
			// skip language key using for object's plugin
			$result['skipped'] = language_scanner_skip_language_keys_from_object_plugin($plugin_name, $language_keys);
			$language_keys = array_diff_key($language_keys, $result['skipped']);

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
						preg_match_all('/(?:elgg_echo|elgg.echo)\((?:\'|\")([^(\'|\")]*)(?:\'|\")/i', $content, $matches);
						if ($matches[1]) $found_key = true;
						
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
	
	function language_scanner_get_content_from_file($file) {
		if(file_exists($file)) {
			if($contents = file_get_contents($file)) {
				return $contents;
			}
		}

		return false;
	}

	function language_scanner_get_plugin_files($plugin_name, $recursive = true) {
		switch($plugin_name) {
			case 'core':
				$directories = array('actions', 'engine', 'js', 'pages', 'views');
				$files = array();
				foreach($directories as $directory) {
					$files = array_merge($files, language_scanner_directory_listing(elgg_get_root_path() . $directory, $recursive));
				}
				return $files;
				break;
			default:
				$directory = elgg_get_plugins_path() . $plugin_name;
				return language_scanner_directory_listing($directory, $recursive);
				break;
		}
	}
	
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
	
	function language_scanner_get_language_keys_from_plugin($plugin_name) {
		$language_arrays = array();

		$plugins_path = elgg_get_plugins_path();		
		$plugin_path = $plugins_path . $plugin_name;

		if($plugin_name == 'core') {
			$language_file = elgg_get_root_path() . 'languages/en.php';
		} else {
			$language_file = $plugin_path . '/languages/en.php';
		}
		
		if(file_exists($language_file)) {
			if($contents = file_get_contents($language_file)) {
				include($language_file);

				$matches = language_scanner_check_variable_name($contents);

				foreach($matches[0] as $match) { // @ManUtopiK: I don't understand this loop. Why ?
					if(!empty($match) && $match != '$CONFIG') {
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

	function language_scanner_skip_language_keys_from_object_plugin($plugin_name, $language_keys) {
		$language_skipped = array();

		$plugins_path = elgg_get_plugins_path();
		$start_file = $plugins_path . $plugin_name . '/start.php';

		if($contents = file_get_contents($start_file)) {
			preg_match_all('/elgg_register_entity_type\(([^;]*)\)/', $contents, $entities);
			foreach($entities[1] as $entity) {
				$entity = preg_replace('/\'|object|,|\s/', '', $entity);
				
				$language_keys_to_skip = array(
					'item:object:'.$entity => '',
					'river:create:object:'.$entity => '',
					'river:update:object:'.$entity => '',
					'river:comment:object:'.$entity => '',
				);

				foreach($language_keys_to_skip as $key => $value) {
					if(array_key_exists($key, $language_keys)) $language_skipped[$key] = $language_keys[$key];
				}

			}
			return $language_skipped;
		} else {
			return false;
		}
	}

	function language_scanner_check_variable_name($string) {
		preg_match_all('(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)', $string, $matches);

		return $matches;
	}
	
	function language_scanner_check_extension($file_name) {
		$extension_array = explode('.', $file_name);
		$extension = end($extension_array);

		if(in_array($extension, array('php', 'html', 'js'))) {
			return true;
		}

		return false;
	}
	
	function language_scanner_scan_missing_key($plugin_name) {
	
		$plugin_files = language_scanner_get_plugin_files($plugin_name);
		
		if($language_keys = language_scanner_get_language_keys_from_plugin($plugin_name)) {
			$language_keys_left = $language_keys;
			$core_language = language_scanner_get_language_keys_from_plugin('core');
			$language_keys = array_merge($language_keys, $core_language);
		
			$resultats = array();
			foreach($plugin_files as $file) {
				$i = 0;
				@ $fp = fopen($file, 'r') or die('Open "' . $file . '" error !');
				while (!feof($fp)) {
					$i++;
					$line = fgets($fp, 1024);
					if (substr(trim($line), 0, 1) == '*') continue; // skip commented line
					preg_match_all('/(?:elgg_echo|elgg.echo)\((?:\'|\")([^(\'|\")]*)(?:\'|\")/i', $line, $matches);
					if ($matches[1]) {
						foreach($matches[1] as $match) {
							if (!array_key_exists($match, $resultats)) {
								if ($match[strlen($match)-1] == ':') $match = $match . '$'; // in case of composed string with a dot
								$resultats[$match][strstr($file, $plugin_name)]['line'] = array($i);
							} else {
								unset($language_keys_left[$match]);
							}
						}
					}
				}
				fclose($fp);
			}
			$result['keys_in_code_found_count'] = count($resultats);
			
			foreach($resultats as $message_key => $where) {
				if (!array_key_exists($message_key, $language_keys)) {
					if ( $message_key_trunk = strstr( $message_key, '$', true) ) {
						$language_keys_var[$message_key] = $where;
						$language_keys_var[$message_key]['trunk'] = str_replace('{', '', $message_key_trunk);
					} else {
						$msg = '';
						foreach($where as $file => $line) {
							$msg .= $file . ' line ' . implode('-', $line['line']);
						}
						$missing_keys[$message_key] = $msg;
					}
				} else {
					unset($language_keys_left[$message_key]);
				}
			}
			$result['missing_key'] = $missing_keys;
			
			foreach($language_keys_var as $message_key => $where) {
				$msg = '';
				foreach($where as $file => $line) { 
					if ( $file != 'trunk' ) {
						if ($msg != '') $msg .= ' | ';
						$msg .= $file . ' line ' . implode('-', $line['line']);
					}
				}
				
				//$similar_founded = false;
				$msg_plugin = $msg_core = '';
				foreach($language_keys_left as $key_left => $value) {
					if (strstr($key_left, $where['trunk'])) {
						if ($msg_plugin != '') $msg_plugin .= ' | ';
						$msg_plugin .= $key_left;
					}
				}
				if ($msg_plugin) {
					$msg .= '<br/>&nbsp;' . elgg_echo('language_scanner:similar:in_plugin') . '<br/>&nbsp;&nbsp;' . $msg_plugin;
				} else {
					foreach($core_language as $core_key => $core_value) {
						if (strstr($core_key, $where['trunk'])) {
							if ($msg_core != '') $msg_core .= ' | ';
							$msg_core .= $core_key;
						}
					}
					if ($msg_core) {
						$msg .= '<br/>&nbsp;' . elgg_echo('language_scanner:similar:in_core') . '<br/>&nbsp;&nbsp;' . $msg_core;
					} else {
						$msg .= '<br/>&nbsp;' . elgg_echo('language_scanner:similar:not_founded');
					}
				}
				
				$missing_keys_var[$message_key] = $msg;
			}
			$result['missing_key_with_var'] = $missing_keys_var;
			global $fb; $fb->info($language_keys_left);
			return $result;
		} else {
			return false;
		}
	}