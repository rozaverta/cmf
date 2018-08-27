<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:40
 */

namespace EApp\Event\Driver;

use EApp\Component\Module;
use EApp\Schemes\EventCallbackSchemeDesigner;
use EApp\Schemes\EventsSchemeDesigner;
use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Interfaces\SystemDriverInterface;

class EventCallbackDriverInterface implements SystemDriverInterface, Loggable
{
	use LoggableTrait;
	use GetModuleComponentTrait;

	public function __construct( Module $module )
	{
		$this->setModule($module);
	}

	public function add( $class_name, $priority = 1 )
	{

	}

	public function rename( $old_class_name, $new_class_name )
	{

	}

	public function priority( $class_name, $priority = 1 )
	{

	}

	public function delete( $class_name )
	{

	}

	public function link( $event, EventCallbackSchemeDesigner $callback_item )
	{
		$event = $this->getEventItem($event);
		$event_id = $event->id;
		$callback_id = $callback_item->id;
	}

	public function unlink( $event, EventCallbackSchemeDesigner $callback_item )
	{
		$event = $this->getEventItem($event);
		$event_id = $event->id;
		$callback_id = $callback_item->id;
	}

	/**
	 * @param string $class_name
	 * @return \EApp\Schemes\EventCallbackSchemeDesigner | bool
	 */
	public function getCallbackItem( string $class_name )
	{
		return \DB
			::table("event_callback")
				->where("class_name", $this->getClassName($class_name))
				->where("module_id", $this->getModule()->getId())
				->select(["*"])
				->limit(1)
				->setResultClass(EventCallbackSchemeDesigner::class)
				->first();
	}

	// protected

	protected function getEventItem( $event ): EventsSchemeDesigner
	{
		if( $event instanceof EventsSchemeDesigner )
		{
			return $event;
		}

		$event_name = (string) $event;
		$event = EventDriverInterface::getEventItem($event_name);
		if( !$event )
		{
			throw new NotFoundException("Event '{$event_name}' not found");
		}

		return $event;
	}

	protected function getClassName( string $class_name ): string
	{
		if( strpos($class_name, "\\") !== false )
		{
			$prefix = $this->getModule()->getNamespace();
			if( $class_name[0] === "\\" )
			{
				$prefix = "\\" . $prefix;
			}

			$len = strlen($prefix);
			if( substr($class_name, 0, $len) === $prefix )
			{
				return substr($class_name, $len);
			}
		}

		return $class_name;
	}
}