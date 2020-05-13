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

namespace W7\PackagePlugin\Processor;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;

abstract class ProcessorAbstract {
	/**
	 * @var Event
	 */
	protected $event;
	/**
	 * @var Composer
	 */
	protected $composer;
	/**
	 * @var IOInterface
	 */
	protected $io;
	/**
	 * @var string
	 */
	protected $vendorPath;
	/**
	 * @var string
	 */
	protected $basePath;
	/**
	 * @var array
	 */
	protected $installedFileData = [];
	/**
	 * @var array
	 */
	protected $autoloadFiles = [];

	public function __construct(Event $event) {
		$this->event = $event;
		$this->composer = $event->getComposer();
		$this->io = $event->getIO();
	}

	public function setInstalledFileContent(array $data) {
		$this->installedFileData = $data;
	}

	public function setVendorPath(string $vendorPath) {
		$this->vendorPath = $vendorPath;
		$this->basePath = dirname($this->vendorPath, 1);
	}

	protected function addAutoloadFiles($files) {
		$files = (array)$files;
		$this->autoloadFiles = array_merge($this->autoloadFiles, $files);
	}

	public function getAutoloadFiles() {
		return $this->autoloadFiles;
	}

	abstract public function process();

	protected function ensureDirectoryExists($directory) {
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

	protected function generateConfigFiles($file, $contents) {
		$filePath = $this->vendorPath . '/composer/rangine/autoload/config/' . $file;
		$this->ensureDirectoryExists(dirname($filePath));
		$contents = '<?php return ' . var_export($contents, true) . ';';
		file_put_contents($filePath, $contents);
	}
}
