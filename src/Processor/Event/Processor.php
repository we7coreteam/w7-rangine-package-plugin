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

namespace W7\PackagePlugin\Processor\Event;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Processor extends ProcessorAbstract {
	public function process() {
		$this->generateConfigFiles('event.php', $this->findEvents());
	}

	/**
	 * 自动发现event和listener 如 app/Event/TestEvent.php 对应app/Listener/TestListener.php. app/Event/Test/TestEvent.php 对应app/Listener/Test/TestListener.php
	 * @return array
	 */
	private function findEvents() {
		$events = [];
		$eventPath = $this->basePath . '/app/Event';
		if (!file_exists($eventPath)) {
			return $events;
		}

		$files = Finder::create()
			->in($eventPath)
			->files()
			->ignoreDotFiles(true)
			->name('/^[\w\W\d]+Event.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$eventName = $file->getRelativePathname();
			$eventName = substr($eventName, 0, strlen($eventName) - 9);
			$eventName = str_replace('/', '\\', $eventName);

			$listenerFile = $eventName . 'Listener.php';
			if (file_exists(dirname($eventPath) . '/Listener/' . str_replace('\\', '/', $listenerFile))) {
				$eventClass = $this->appNamespace . '\\Event\\' . $eventName . 'Event';
				$listenerClass = $this->appNamespace . '\\Listener\\' . $eventName . 'Listener';
				$events[$eventClass] = $listenerClass;
			}
		}

		return $events;
	}
}
