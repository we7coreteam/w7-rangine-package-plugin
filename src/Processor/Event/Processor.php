<?php

namespace W7\PackagePlugin\Processor\Event;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\PackagePlugin\Processor\ProcessorAbstract;

class Processor extends ProcessorAbstract {
	public function process($vendorPath) {
		$dir = dirname($vendorPath, 1) . '/app/Event';
		$appEvents = $this->findEvents($dir, 'W7\\App');
		$this->generateEventConfig($appEvents, $vendorPath);
	}

	/**
	 * 自动发现event和listener 如 app/Event/TestEvent.php 对应app/Listener/TestListener.php. app/Event/Test/TestEvent.php 对应app/Listener/Test/TestListener.php
	 * @param $path
	 * @param $classNamespace
	 * @return array
	 */
	private function findEvents($path, $classNamespace) {
		$events = [];

		$files = Finder::create()
			->in($path)
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
			$listenerClass = $classNamespace . '\\Listener\\' . $eventName . 'Listener';

			$eventClass = $classNamespace . '\\Event\\' . $eventName . 'Event';
			$events[$eventClass] = $listenerClass;
		}

		return $events;
	}

	private function generateEventConfig($events, $vendorPath) {
		$content = "<?php\r\nfunction iUserEvents() { \r\n	return [\r\n";
		foreach ($events as $event => $listeners) {
			$listeners = (array)$listeners;
			$content .= "		'" . $event . "' => [\r\n";
			foreach ($listeners as $listener) {
				$content .= "			'" . $listener . "',";
			}
			$content .= "\r\n		],\r\n";
		}
		$content .="	];\r\n}";

		$eventFile = $vendorPath  . '/composer/rangine/autoload/event.php';
		if (!is_dir(dirname($eventFile))) {
			mkdir(dirname($eventFile), 0777, true);
		}
		file_put_contents($eventFile, $content);
		$this->addAutoloadFiles($eventFile);
	}
}