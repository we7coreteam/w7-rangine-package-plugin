<?php

namespace W7\PackagePlugin\Processor\Provider;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Processor extends ProcessorAbstract {
	public function process($vendorPath) {
		$vendorProviders = $this->findVendorProviders();
		$appProviders = $this->findAppProviders($vendorPath);
		$this->generateProviderConfig(array_merge($vendorProviders, $appProviders), $vendorPath);
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

	private function findAppProviders($vendorPath) {
		$dir = dirname($vendorPath, 1) . '/app/Provider';
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

	private function generateProviderConfig($providers, $vendorPath) {
		$content = "<?php\r\nfunction iUserProviders() { \r\n	return [\r\n";
		foreach ($providers as $name => $provider) {
			$content .= "		'" . $name . "' => [\r\n";
			foreach ($provider as $item) {
				$content .= "			'" . $item . "',";
			}
			$content .= "\r\n		],\r\n";
		}
		$content .="	];\r\n}";

		$providerFile = $vendorPath  . '/composer/rangine/autoload/provider.php';
		if (!is_dir(dirname($providerFile))) {
			mkdir(dirname($providerFile), 0777, true);
		}
		file_put_contents($providerFile, $content);
		$this->addAutoloadFiles($providerFile);
	}
}