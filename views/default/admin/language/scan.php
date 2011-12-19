<?php

if($plugin_name = get_input('plugin_name'))
{
	$language_scanner_result = language_scanner_scan_language($plugin_name);
	
	echo 'Found language keys: ' . $language_scanner_result['start_count'] . '<br />';
	echo 'Unused keys: ' . $language_scanner_result['end_count'] . '<br />';
	
	if($language_scanner_result['unused'])
	{
		echo '<ul style="list-style-type: disc;">';
		foreach($language_scanner_result['unused'] as $key => $value)
		{
			echo '<li>' . strip_tags($key) . '</li>';
		}
		echo '</ul>';
	}
	
}
