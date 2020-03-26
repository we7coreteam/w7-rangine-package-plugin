<?php

namespace W7\PackagePlugin\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Plugin implements PluginInterface, EventSubscriberInterface {
	/**
	 * @var \Composer\Composer
	 */
	protected $composer;

	/**
	 * @var \Composer\IO\IOInterface
	 */
	protected $io;

	/**
	 * Apply plugin modifications to Composer
	 *
	 * @param Composer    $composer
	 * @param IOInterface $io
	 */
	public function activate(Composer $composer, IOInterface $io) {
		$this->composer = $composer;
		$this->io = $io;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'post-autoload-dump' => 'initPackage',
		);
	}

	private function getProviderConfigPath($vendorPath) {
		return dirname($vendorPath, 1)  . '/config/provider.php';
	}

	public static function initPackage(\Composer\Script\Event $event) {
		$plugin = new static();
		$plugin->activate($event->getComposer(), $event->getIO());

		$config = $plugin->composer->getConfig();
		$filesystem = new Filesystem();
		$vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));

		if (!is_dir(dirname($plugin->getProviderConfigPath($vendorPath)))) {
			return false;
		}

		$vendorProviders = $plugin->findVendorProviders($vendorPath);
		$appProviders = $plugin->findAppProviders($vendorPath);
		$plugin->generateProviderConfig(array_merge($vendorProviders, $appProviders), $vendorPath);
	}

	private function findVendorProviders($vendorPath) {
		$installedFile = $vendorPath.'/composer/installed.json';
		ob_start();
		include $installedFile;
		$content = ob_get_clean();
		$content = json_decode($content, true);

		$providers = [];
		foreach ($content as $item) {
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
		$content = "<?php \r\nreturn [\r\n";
		foreach ($providers as $name => $provider) {
			$content .= "	'" . $name . "' => [\r\n";
			foreach ($provider as $item) {
				$content .= "		'" . $item . "',";
			}
			$content .= "\r\n	],\r\n";
		}
		$content .='];';

		file_put_contents($this->getProviderConfigPath($vendorPath), $content);
	}
}