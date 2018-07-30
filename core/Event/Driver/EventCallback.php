<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:40
 */

namespace EApp\Event\Driver;


use EApp\Component\Module;
use EApp\Event\Scheme\EventCallbackSchemeDesigner;
use EApp\Event\Scheme\EventSchemeDesigner;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;

class EventCallback implements SystemDriver, Loggable
{
	use LoggableTrait;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	public function __construct( Module $module )
	{
		$this->module = $module;
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->module;
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
	 * @param string|int $class_name
	 * @return \EApp\Event\Scheme\EventCallbackSchemeDesigner | bool
	 */
	public function getCallbackItem( $class_name )
	{
		$class_name = $this->getClassName($class_name);

		$con = \DB::connection();
		$sql = $con->table("event_callback")
			->where("class_name", $class_name)
			->where("module_id", $this->module->getId())
			->select(["*"])
			->limit(1)
			->toSql();

		$result = $con->select($sql, [], true, EventCallbackSchemeDesigner::class);
		return count($result) ? reset($result) : false;
	}

	// protected

	protected function getEventItem($event)
	{
		if( $event instanceof EventSchemeDesigner )
		{
			return $event;
		}

		$event_name = (string) $event;
		$event = EventFactory::getEventItem($event_name);
		if( !$event )
		{
			throw new NotFoundException("Event '{$event_name}' not found");
		}

		return $event;
	}

	protected function getClassName( $class_name )
	{
		$end = stripos($class_name, "\\");
		if( $end !== false )
		{
			$prefix = $this->module->get("name_space") . "Events\\";
			$len = strlen($prefix);
			if( substr($class_name, 0, $len) === $prefix && $end === $len )
			{
				$class_name = substr($class_name, $len);
			}
		}

		return $class_name;
	}
}