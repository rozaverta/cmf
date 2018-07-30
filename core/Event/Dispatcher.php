<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace EApp\Event;

use EApp\Event\Exceptions\EventAbortException;
use EApp\Event\Exceptions\EventOverloadException;
use EApp\Event\Interfaces\EventInterface;
use EApp\Support\Collection;

class Dispatcher
{
	/**
	 * Event name
	 *
	 * @type string
	 */
	private $name = '';

	/**
	 * Callbacks
	 *
	 * @type \SplPriorityQueue
	 */
	private $callbacks;

	/**
	 * Registered name
	 *
	 * @type array
	 */
	private $registered = [];

	private $complete = [];

	private $aborted = false;

	/**
	 * Is completable
	 *
	 * @var bool
	 */
	private $completable;

	/**
	 * Dispatcher in progress
	 *
	 * @type bool
	 */
	private $is_run = false;

	/**
	 * @var null|\Closure
	 */
	private $preparatory = null;

	public function __construct( $name, $completable = false, \Closure $preparatory = null )
	{
		$this->name = $name;
		$this->completable = (bool) $completable;
		$this->callbacks = new \SplPriorityQueue();
		$this->preparatory = $preparatory;
	}

	public function getName()
	{
		return $this->name;
	}

	public function count()
	{
		return $this->callbacks->count();
	}

	public function isRun()
	{
		return $this->is_run;
	}

	/**
	 * @param \Closure $callback $callback
	 * @param int $priority
	 * @return $this
	 */
	public function listen( \Closure $callback, $priority = 0)
	{
		$this->callbacks->insert( $callback, (int) $priority );
		return $this;
	}

	/**
	 * @param \Closure $callback
	 * @param $name
	 * @return $this
	 */
	public function register(\Closure $callback, $name)
	{
		if(! $this->isRegistered($name))
		{
			$this->registered[] = $name;
			$callback($this);
		}
		return $this;
	}

	public function isRegistered($name)
	{
		return in_array($name, $this->registered);
	}

	public function aborted()
	{
		return $this->aborted;
	}

	public function completable()
	{
		return count($this->complete) > 0;
	}

	public function abort()
	{
		if( $this->isRun() )
		{
			throw new EventAbortException;
		}
	}

	/**
	 * Dispatch events
	 *
	 * @param EventInterface|array $event
	 * @param \Closure|null $callback
	 * @return Collection
	 */
	public function dispatch($event = [], \Closure $callback = null)
	{
		if( $this->isRun() )
		{
			throw new EventOverloadException("The event '{$this->name}' is already running");
		}

		// clean data

		$this->aborted = false;
		$this->complete = [];
		$dispatch = new Collection();

		if( !is_null($this->preparatory) )
		{
			$prepare = $this->preparatory;
			if( $prepare($this) === false )
			{
				$this->aborted = true;
				return $dispatch;
			}
		}

		if( ! $this->count() )
		{
			return $dispatch;
		}

		if( !$event instanceof EventInterface )
		{
			$event = new Event($this->name, $event);
		}
		else if( $event->getName() !== $this->name )
		{
			throw new \InvalidArgumentException("The name of the Event does not match the name of the Dispatcher");
		}

		$isCall = $callback instanceof \Closure;
		$this->is_run = true;

		foreach( clone $this->callbacks as $call )
		{
			try
			{
				$result = call_user_func( $call, $event );
			}
			catch( EventAbortException $e )
			{
				$this->aborted = true;
				break;
			}

			if( $isCall && $callback($result) === false )
			{
				$this->aborted = true;
				break;
			}

			if( $this->completable && $result instanceof \Closure )
			{
				$this->complete[] = $result;
			}
			else if( !is_null($result) )
			{
				$dispatch[] = $result;
			}
		}

		$this->is_run = false;

		return $dispatch;
	}

	public function complete(... $args)
	{
		foreach( $this->complete as $call )
		{
			$call(... $args);
		}
		$this->complete = [];
	}

	/**
	 * @return Dispatcher
	 */
	public function getCompletableClone()
	{
		$clone = new self($this->name, $this->completable);
		if($this->completable)
		{
			$clone->complete = $this->complete;
			$this->complete = [];
		}
		return $clone;
	}
}