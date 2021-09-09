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
	protected $autoReloadPaths = [];
	protected $openBaseDirs = [];

	public function process() {
		$vendorProviders = $this->findVendorProviders();
		$appProviders = $this->findAppProviders();

		$this->generateConfigFiles('provider.php', ['providers' => array_merge($vendorProviders, $appProviders)]);
		$basePathReplaceKey = "'" . '$basePath';
		$replaces = [
			'<?php ' => '<?php' . "\n\n" . '$basePath = dirname(dirname(__FILE__),5);' . "\n",
			$basePathReplaceKey => '"$basePath" . ' . "'"
		];
		$this->generateConfigFiles('reload.php', ['path' => $this->autoReloadPaths, 'type' => []], $replaces);
		$appConfig = [
			'setting' => [
				'basedir' => $this->openBaseDirs
			]
		];
		$this->generateConfigFiles('app.php', $appConfig, $replaces);
	}

	private function findVendorProviders() {
		$providers = [];
		foreach ($this->installedFileData as $item) {
			if (!empty($item['extra']['rangine']['providers'])) {
				$providers[$item['name']] = $item['extra']['rangine']['providers'];
				//添加autoload path
				if ($item[$item['installation-source']]['type'] == 'path') {
					$path = '$basePath' . '/' . $item[$item['installation-source']]['url'];
				} else {
					$path = '$basePath' . '/vendor/' . $item['name'];
				}
				$path .= '/';
				$this->autoReloadPaths[] = $path;
				//添加安全目录
				$this->openBaseDirs[] = $path;
			}
		}

		return $providers;
	}

	private function findAppProviders() {
		$providers = [];
		$providerPath = $this->basePath . '/app/Provider';
		if (!is_dir($providerPath)) {
			return $providers;
		}

		$namespace = $this->appNamespace . '\\Provider';
		$files = Finder::create()
			->in($providerPath)
			->files()
			->ignoreDotFiles(true)
			->name('/^[\w\W\d]+Provider.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$path = str_replace([$providerPath, '.php', '/'], [$namespace, '', '\\'], $file->getPathname());
			$providers[$path] = [$path];
		}

		return $providers;
	}
}
