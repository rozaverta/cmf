<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.09.2017
 * Time: 1:52
 */

namespace EApp\Event;

use EApp\Cache;
use EApp\Event\Interfaces\EventInterface;
use EApp\Helper;
use EApp\Exceptions\NotFoundException;
use EApp\Exceptions\WriteException;

final class EventManager
{
	/**
	 * Get event manager element from cache
	 *
	 * @param $name
	 * @return \EApp\Event\Dispatcher
	 * @throws \Exception
	 */
	public static function dispatcher($name)
	{
		static $all = [];

		if(! isset($all[$name]))
		{
			// system has been installed
			// use default mode
			if(Helper::isSystemInstall())
			{
				$cache = new Cache($name, 'events');

				if( $cache->ready() )
				{
					$data = $cache->import();
				}
				else
				{
					$event = new EventFactory($name);
					if( $event->load() === false )
					{
						throw new NotFoundException("Event '{$name}' is not registered in system");
					}

					$data = $event->getContentData();
					if(! $cache->export($data))
					{
						throw new WriteException("Can't write cache data for the '{$name}' event");
					}
				}
			}
			else
			{
				$data = (new EventFactory($name))->getContentData();
			}

			$manager = new Dispatcher(
				$data["name"],
				$data["completable"],
				function (Dispatcher $manager) use ($data) {
					foreach($data["classes"] as $class_name)
					{
						/** @var \EApp\Event\Interfaces\EventPrepareInterface $class */
						$class = new $class_name($data["name"]);
						$class->prepare($manager);
					}
				});

			$all[$name] = $manager;
		}

		return $all[$name];
	}

	public static function listen($name, \Closure $callback, $priority = 0)
	{
		return self::dispatcher($name)->listen($callback, $priority);
	}

	public static function dispatch(EventInterface $event, \Closure $callback = null)
	{
		return self::dispatcher($event->getName())->dispatch($event, $callback);
	}

	public static function isRun($name)
	{
		return self::dispatcher($name)->isRun();
	}

	public static function count($name)
	{
		return self::dispatcher($name)->count();
	}
}