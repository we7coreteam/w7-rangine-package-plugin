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

namespace W7\PackagePlugin\Helper;

class Helper {
	public static function ensureDirectoryExists($directory) {
		if (!is_dir($directory)) {
			if (file_exists($directory)) {
				throw new \RuntimeException(
					$directory.' exists and is not a directory.'
				);
			}
			if (!@mkdir($directory, 0777, true)) {
				throw new \RuntimeException(
					$directory.' does not exist and could not be created.'
				);
			}
		}
	}
}
