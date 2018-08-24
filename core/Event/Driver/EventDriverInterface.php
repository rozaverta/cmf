<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:40
 */

namespace EApp\Event\Driver;

use EApp\Event\Driver\Events\EventCreateDriverEvent;
use EApp\Event\Driver\Events\EventDeleteDriverEvent;
use EApp\Event\Driver\Events\EventRenameDriverEvent;
use EApp\Event\Driver\Events\EventUpdateDriverEvent;
use EApp\Component\Module;
use EApp\Event\EventManager;
use EApp\Event\Scheme\EventSchemeDesigner;
use EApp\Prop;
use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Interfaces\SystemDriverInterface;
use EApp\Text;

class EventDriverInterface implements SystemDriverInterface, Loggable
{
	use LoggableTrait;
	use GetModuleComponentTrait;

	public function __construct( Module $module )
	{
		$this->setModule($module);
	}

	/**
	 * Create (register) new event
	 *
	 * @param string $name
	 * @param string $title
	 * @param bool $completable
	 * @return Prop
	 */
	public function create( string $name, string $title = "", bool $completable = false ): Prop
	{
		if( self::hasName($name) )
		{
			throw new \InvalidArgumentException("Duplicate event name '{$name}'");
		}

		if( ! $this->isValidModuleName($name) )
		{
			throw new \InvalidArgumentException("Invalid event name '{$name}' for the " . $this->getModule()->getName() . " module");
		}

		$title = trim($title);
		if( strlen($title) < 1 )
		{
			$title = $name . " event";
		}

		// dispatch event
		$event = new EventCreateDriverEvent($this, $name, $title, $completable);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher->dispatch($event);

		$prop = new Prop([
			"action" => "insert"
		]);

		$module_id = $this->getModule()->getId();
		$prop->set(
			"id",
			\DB::
				table("events")
					->insertGetId(compact('name', 'title', 'module_id', 'completable'))
		);

		$this->addLogDebug(Text::createInstance("The %s event is added to the database", $name));
		$dispatcher->complete($prop);
		return $prop;
	}

	/**
	 * Update event data
	 *
	 * @param string $name
	 * @param string $title
	 * @param bool $completable
	 * @return Prop
	 * @throws NotFoundException
	 */
	public function update( string $name, string $title = "", bool $completable = false ): Prop
	{
		$row = self::getEventItem($name);
		if( !$row )
		{
			throw new NotFoundException("Event '{$name}' not found");
		}

		$this->permissible($row->module_id, $name);

		$title = trim($title);
		if( strlen($title) < 1 )
		{
			$title = $name . " event";
		}

		$prop = new Prop([
			"action" => "update",
			"updated" => false
		]);

		if( $title === $row->title && $completable === $row->completable )
		{
			return $prop;
		}

		// dispatch event
		$event = new EventUpdateDriverEvent($this, $name, $title, $completable);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher->dispatch($event);

		$prop->set(
			"updated",
			\DB::
				table("events")
					->whereId($row->id)
					->update(compact('title', 'completable')) > 0
		);

		$prop->get("updated") && $this->addLogDebug(Text::createInstance("The %s event is successfully updated", $name));
		$dispatcher->complete($prop);
		return $prop;
	}

	/**
	 * Create (register) or update event
	 *
	 * @param string $name
	 * @param string $title
	 * @param bool $completable
	 * @return Prop
	 */
	public function replace( string $name, string $title = "", bool $completable = false ): Prop
	{
		if( self::hasName($name) )
		{
			return $this->update($name, $title, $completable);
		}
		else
		{
			return $this->create($name, $title, $completable);
		}
	}

	/**
	 * Rename event
	 *
	 * @param string $old_name
	 * @param string $new_name
	 * @return Prop
	 * @throws NotFoundException
	 */
	public function rename( string $old_name, string $new_name ): Prop
	{
		// check old
		$row = self::getEventItem($old_name);
		if( !$row )
		{
			throw new NotFoundException("Event '{$old_name}' not found");
		}

		$this->permissible($row->module_id, $old_name);

		// is update ?

		$prop = new Prop([
			"action" => "rename",
			"renamed" => false
		]);

		if( $old_name === $new_name )
		{
			return $prop;
		}

		// check new
		if( self::hasName($new_name) )
		{
			throw new \InvalidArgumentException("Duplicate event name '{$new_name}'");
		}

		if( ! $this->isValidModuleName($new_name) )
		{
			throw new \InvalidArgumentException("Invalid event name '{$new_name}' for the " . $this->getModule()->getName() . " module");
		}

		// dispatch event
		$event = new EventRenameDriverEvent($this, $old_name, $new_name);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher->dispatch($event);

		$prop->set(
			"renamed",
			\DB::
				table("events")
					->whereId($row->id)
					->update(['name' => $new_name]) > 0
		);

		$prop->get("renamed") && $this->addLogDebug(Text::createInstance("The %s event is successfully renamed. New event name is %s", $old_name, $new_name));
		$dispatcher->complete($prop);
		return $prop;
	}

	/**
	 * Delete event
	 *
	 * @param string $name
	 * @return Prop
	 */
	public function delete( string $name ): Prop
	{
		$prop = new Prop([
			"action" => "delete",
			"deleted" => false
		]);

		// check old
		$row = self::getEventItem($name);
		if( !$row )
		{
			return $prop;
		}

		$this->permissible($row->module_id, $name);

		// dispatch event
		$event = new EventDeleteDriverEvent($this, $name);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher->dispatch($event);

		$prop->set(
			"deleted",
			\DB::
				table("events")
					->whereId($row->id)
					->delete() > 0
		);

		$prop->get("deleted") && $this->addLogDebug(Text::createInstance("The %s event is successfully removed", $name));
		$dispatcher->complete($prop);
		return $prop;
	}

	// protected

	protected function permissible( int $module_id, string $name )
	{
		if( $module_id !== $this->getModule()->getId() )
		{
			throw new \InvalidArgumentException(
				"It is permissible to change the '{$name}' event data only for the '" . $this->getModule()->getName() . "' module"
			);
		}
	}

	protected function isValidModuleName($name)
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		if($this->getModule()->getId() === 0)
		{
			return true;
		}

		$pref = "on" . $this->getModule()->getName(); // for User module event name = onUserEventName
		$len = strlen($pref);
		return strlen($name) > $len && substr($name, 0, $len) === $pref && ctype_upper($name[$len]);
	}

	// static functions

	/**
	 * @param $name
	 * @return \EApp\Event\Scheme\EventSchemeDesigner | bool
	 */
	public static function getEventItem( $name )
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		$con = \DB::connection();
		$sql = $con->table("events")
			->where("name", $name)
			->select(["*"])
			->limit(1)
			->toSql();

		$row = $con->selectOne($sql, [], true, EventSchemeDesigner::class);
		return $row;
	}

	/**
	 * Config name is exists
	 *
	 * @param string $name
	 * @param null | int $module_id
	 * @return bool
	 */
	public static function hasName( $name, $module_id = null ): bool
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		$builder = \DB::table("events")->where("name", $name);
		if( is_numeric($module_id) )
		{
			$builder->where("module_id", (int) $module_id );
		}

		return $builder->count(["id"]) > 0;
	}

	/**
	 * Validate config name
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function isValidName( $name ): bool
	{
		$len = strlen($name);
		if( $len < 5 || ! preg_match('/^on[A-Z][a-zA-Z]*$/', $name) )
		{
			return false;
		}

		return $len < 256;
	}
}