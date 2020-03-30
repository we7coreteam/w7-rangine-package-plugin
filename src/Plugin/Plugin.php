<?php

namespace W7\PackagePlugin\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use ComposerIncludeFiles\Composer\AutoloadGenerator;
use W7\PackagePlugin\Processor\ProcessorAbstract;
use W7\PackagePlugin\Processor\Provider\Processor as ProviderProcessor;
use W7\PackagePlugin\Processor\Event\Processor as EventProvessor;

class Plugin implements PluginInterface, EventSubscriberInterface {
	/**
	 * @var Composer
	 */
	protected $composer;

	/**
	 * @var IOInterface
	 */
	protected $io;

	protected $processors = [
		ProviderProcessor::class,
		EventProvessor::class
	];

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

		$installedFile = $vendorPath.'/composer/installed.json';
		ob_start();
		include $installedFile;
		$installedFileData = ob_get_clean();
		$this->installedFileData = json_decode($installedFileData, true);
		return $this->installedFileData;
	}

	private function addAutoloadFiles(array $files) {
		if (!$files) {
			return true;
		}
		$generator = new AutoloadGenerator($this->composer->getEventDispatcher(), $this->io);
		$generator->dumpFiles($this->composer, $files);
	}

	public static function initPackage(Event $event) {
		$plugin = new static();
		$plugin->activate($event->getComposer(), $event->getIO());

		$vendorPath = $plugin->getVendorPath();
		$installedFileData = $plugin->getInstalledFileData($vendorPath);

		$autoloadFiles = [];
		foreach ($plugin->processors as $processor) {
			/**
			 * @var ProcessorAbstract $processor
			 */
			$processor = new $processor($event);
			$processor->setInstalledFileContent($installedFileData);
			$processor->process($vendorPath);
			$autoloadFiles = array_merge($autoloadFiles, $processor->getAutoloadFiles());
		}

		$plugin->addAutoloadFiles($autoloadFiles);
	}
}