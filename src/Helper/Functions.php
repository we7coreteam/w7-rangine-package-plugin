<?php

/**
 * Rangine package plugin
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

if (!function_exists('iautoLoadConfig')) {
	function iautoLoadConfig($fileName, $default = []) {
		$configFilePath = BASE_PATH  . '/vendor/composer/rangine/autoload/config/' . strtolower($fileName) . '.php';
		if (file_exists($configFilePath)) {
			return include $configFilePath;
		}

		return $default;
	}
}
