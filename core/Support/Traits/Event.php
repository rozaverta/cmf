<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace EApp\Support\Traits;

use EApp\Event\EventManager;

trait Event
{
	/**
	 * @var null | \EApp\Event\Dispatcher
	 */
	private $dispatcher = null;

	private $listeners = [];

	private $_eventCallbacks = [];
	private $_eventResult = null;
	private $_eventProgress = false;

	public function listen( \Closure $callback, $priority = 0 )
	{
		$this->listeners[] = [ $callback, $priority ];
		return $this;
	}

	protected function eventDispatcher()
	{
		return $this->dispatcher;
	}

	protected function eventIsRun()
	{
		return $this->dispatcher && $this->dispatcher->isRun();
	}

	protected function eventDispatch( $name, $event = null, \Closure $callback = null )
	{
		$this->eventClean();
		$this->dispatcher = EventManager::dispatcher($name);
		foreach( $this->listeners as $listener ) {
			$this->dispatcher->listen(...$listener);
		}

		return $this->dispatcher->dispatch($event, $callback);
	}

	protected function eventDispatchFallible( $name, $event = null )
	{
		$this->eventClean();
		$this->dispatcher = EventManager::dispatcher($name);
		foreach( $this->listeners as $listener ) {
			$this->dispatcher->listen(...$listener);
		}

		return $this->dispatcher->dispatch($event, function($result)
		{
			return $result === false ? false : true;
		});
	}

	protected function eventComplete(...$args)
	{
		$this->dispatcher && $this->dispatcher->completable() && $this->dispatcher->complete(...$args);
		$this->eventClean();
	}

	private function eventClean()
	{
		if( $this->dispatcher )
		{
			if( $this->dispatcher->isRun() )
			{
				$this->dispatcher->abort();
			}
			$this->dispatcher = null;
		}
	}
}