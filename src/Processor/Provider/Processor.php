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

namespace W7\PackagePlugin\Processor\Provider;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Processor extends ProcessorAbstract {
	public function process() {
		$vendorProviders = $this->findVendorProviders();
		$appProviders = $this->findAppProviders();
		$this->generateConfigFiles('provider.php', array_merge($vendorProviders, $appProviders));
	}

	private function findVendorProviders() {
		$providers = [];
		foreach ($this->installedFileData as $item) {
			if (!empty($item['extra']['rangine']['providers'])) {
				$providers[str_replace('/', '.', $item['name'])] = $item['extra']['rangine']['providers'];
			}
		}

		return $providers;
	}

	private function findAppProviders() {
		$dir = $this->basePath . '/app/Provider';
		$namespace = 'W7/App/Provider';
		$providers = [];
		if (!is_dir($dir)) {
			return $providers;
		}

		$files = Finder::create()
			->in($dir)
			->files()
			->ignoreDotFiles(true)
			->name('/^[\w\W\d]+Provider.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$path = str_replace([$dir, '.php', '/'], [$namespace, '', '\\'], $file->getRealPath());
			$providers[$path] = [$path];
		}

		return $providers;
	}
}
