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

namespace W7\PackagePlugin\Processor\Handler;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Processor extends ProcessorAbstract {
	protected $supportHandlerType = [
		'Session',
		'Cache',
		'Log',
		'View'
	];

	public function process() {
		$this->generateConfigFiles('handler.php', $this->getHandlers());
	}

	private function getHandlers() {
		$handlers = [];
		foreach ($this->supportHandlerType as $item) {
			$userHandlers = $this->findHandlers($this->basePath . '/app/Handler' . '/' . $item, $this->appNamespace . '\\Handler\\' . $item);
			$frameHandlers = $this->findHandlers($this->vendorPath . '/w7/rangine/Src/Core/' . $item . '/Handler', sprintf('W7\\Core\\%s\\Handler', $item));
			$handlers[strtolower($item)] = array_merge($frameHandlers, $userHandlers);
		}
		return $handlers;
	}

	/**
	 * @param $path
	 * @param $classNamespace
	 * @return array
	 */
	private function findHandlers($path, $classNamespace) {
		$handlers = [];
		if (!file_exists($path)) {
			return $handlers;
		}

		$files = Finder::create()
			->in($path)
			->files()
			->ignoreDotFiles(true)
			->name('/^[\w\W\d]+Handler.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$handlerName = $file->getRelativePathname();
			$handlerName = substr($handlerName, 0, strlen($handlerName) - 11);
			$handlerName = str_replace('/', '\\', $handlerName);
			$handlerClass = $classNamespace . '\\' . $handlerName . 'Handler';
			$handlers[strtolower($handlerName)] = $handlerClass;
		}

		return $handlers;
	}
}
