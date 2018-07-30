<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:40
 */

namespace EApp\Event\Driver;


use EApp\Component\Module;
use EApp\Event\Scheme\EventSchemeDesigner;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;

class EventFactory implements SystemDriver, Loggable
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

	public function create( $name, $title = null, $completable = false )
	{
		if( self::hasName($name) )
		{
			throw new \InvalidArgumentException("Duplicate event name '{$name}'");
		}

		if( !$title )
		{
			$title = $name . " event";
		}
	}

	public function update( $name, $title = null, $completable = false )
	{

	}

	public function replace( $name, $title = null, $completable = false )
	{

	}

	public function rename( $old_name, $new_name )
	{

	}

	public function delete( $name )
	{

	}

	// protected

	protected function getId()
	{

	}

	// protected function

	protected function isValidModuleName($name)
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		if($this->module->getId() === 0)
		{
			return true;
		}

		$pref = "onModule" . $this->module->get("name"); // for User module event name = onModuleUserEventName
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

		$result = $con->select($sql, [], true, EventSchemeDesigner::class);
		return count($result) ? reset($result) : false;
	}

	/**
	 * Config name is exists
	 *
	 * @param string $name
	 * @param null | int $module_id
	 * @return bool
	 */
	public static function hasName( $name, $module_id = null )
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
	public static function isValidName( $name )
	{
		$len = strlen($name);
		if( $len < 5 || ! preg_match('/^on[A-Z][a-zA-Z]*$/', $name) )
		{
			return false;
		}

		return $len < 256;
	}
}