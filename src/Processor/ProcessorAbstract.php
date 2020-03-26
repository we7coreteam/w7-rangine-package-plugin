<?php

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

	protected function addAutoloadFiles($files) {
		$files = (array)$files;
		$this->autoloadFiles = array_merge($this->autoloadFiles, $files);
	}

	public function getAutoloadFiles() {
		return $this->autoloadFiles;
	}

	abstract public function process($vendorPath);
}