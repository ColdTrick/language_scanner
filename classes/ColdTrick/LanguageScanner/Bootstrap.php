<?php

namespace ColdTrick\LanguageScanner;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		elgg_register_ajax_view('language_scanner/report');
	}
}
