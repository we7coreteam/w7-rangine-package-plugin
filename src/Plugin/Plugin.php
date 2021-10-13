<?php

/**
 * WeEngine Api System
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\PackagePlugin\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use ComposerIncludeFiles\Composer\AutoloadGenerator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Plugin implements PluginInterface, EventSubscriberInterface {
	/**
	 * @var Composer
	 */
	protected $composer;

	/**
	 * @var IOInterface
	 */
	protected $io;

	protected $installedFileData = [];

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

	public function deactivate(Composer $composer, IOInterface $i) {
	}

	public function uninstall(Composer $composer, IOInterface $i) {
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return array(
			'post-autoload-dump' => 'initPackage',
		);
	}

	private function getVendorPath() {
		$config = $this->composer->getConfig();
		$filesystem = new Filesystem();
		return $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));
	}

	private function getInstalledFileData($vendorPath) {
		if ($this->installedFileData) {
			return $this->installedFileData;
		}

		$installedFile = $vendorPath . '/composer/installed.json';
		ob_start();
		include $installedFile;
		$installedFileData = ob_get_clean();
		$this->installedFileData = json_decode($installedFileData, true);
		$this->installedFileData = $this->installedFileData['packages'] ?? $this->installedFileData;
		return $this->installedFileData;
	}

	private function addAutoloadFiles(array $files) {
		if (!$files) {
			return true;
		}

		$generator = new AutoloadGenerator($this->composer->getEventDispatcher(), $this->io);
		$generator->dumpFiles($this->composer, $files);
	}

	public function getAppNamespaceFromComposer() {
		$basePath = dirname($this->getVendorPath(), 1);
		$composer = json_decode(file_get_contents($basePath . '/composer.json'), true);

		$psr4 = $composer['autoload']['psr-4'] ?? [];
		foreach ((array) $psr4 as $namespace => $path) {
			foreach ((array) $path as $pathChoice) {
				if (realpath($basePath . '/app') === realpath($basePath . '/' . ltrim($pathChoice, '/'))) {
					$namespace = trim($namespace, '\\');
					return $namespace;
				}
			}
		}

		return 'W7\\App';
	}

	public static function initPackage(Event $event) {
		$plugin = new static();
		$plugin->activate($event->getComposer(), $event->getIO());

		$vendorPath = $plugin->getVendorPath();
		$installedFileData = $plugin->getInstalledFileData($vendorPath);
		$appNamespace = $plugin->getAppNamespaceFromComposer();

		$autoloadFiles = [];
		foreach ($plugin->findProcessor() as $processor) {
			/**
			 * @var ProcessorAbstract $processor
			 */
			$processor = new $processor($event);
			$processor->setAppNamespace($appNamespace);
			$processor->setVendorPath($vendorPath);
			$processor->setInstalledFileContent($installedFileData);
			$processor->process();
			$autoloadFiles = array_merge($autoloadFiles, $processor->getAutoloadFiles());
		}

		$plugin->generateAppNamespaceDefineFile($appNamespace);
		$plugin->addAutoloadFiles($autoloadFiles);
	}

	protected function findProcessor() {
		$processors = [];
		$path = dirname(__DIR__, 1) . '/Processor/';
		if (!file_exists($path)) {
			return $processors;
		}

		$files = Finder::create()
			->in($path)
			->files()
			->ignoreDotFiles(true)
			->name('/^Processor.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$processorName = $file->getRelativePathname();
			$processorName = str_replace(['/', '.php'], ['\\', ''], $processorName);
			$processorName = '\W7\PackagePlugin\Processor\\' . $processorName;
			$processors[] = $processorName;
		}

		return $processors;
	}

	protected function generateAppNamespaceDefineFile($appNamespace) {
		$filePath = dirname(__DIR__) . '/Define/namespace.php';

		$contents = "<?php \n\r" . "!defined('APP_NAMESPACE') && define('APP_NAMESPACE', '{$appNamespace}');";
		file_put_contents($filePath, $contents);
	}
}
