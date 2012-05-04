<?php

	$english = array(
		'language_scanner' => "Language scanner",
		
		'admin:language' => "Languages",
		'admin:language:scan' => "Scan a plugin",
		'admin:administer_utilities:language_scanner' => 'Language scanner',
	
		'language_scanner:admin:pick_plugin' => "Pick a plugin below to scan for unused language keys",
		
		'language_scanner:result:title' => "Scan results for %s",
		'language_scanner:result:total_keys' => "%d keys found in plugin language file",
		'language_scanner:result:unused_keys' => "Unused keys: %d",
		'language_scanner:result:skipped_keys' => "Skipped keys: <span class='elgg-subtext'>(used for object or river)</span>",
		
		'language_scanner:result:total_keys_in_files' => "%d language keys found in plugin code",
		'language_scanner:result:missing_keys' => "Missing keys: %d",
		'language_scanner:result:keys_with_var' => "Skipped keys: %d <span class='elgg-subtext'>(keys with variable)</span>",
		
		'language_scanner:similar:in_plugin' => "Similar key founded in plugin language file:",
		'language_scanner:similar:in_core' => "Similar key founded in core language file:",
		'language_scanner:similar:not_founded' => "No similar key founded.",
	);
	
	add_translation("en", $english);