<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.04.2018
 * Time: 21:46
 */

namespace EApp\Config\Driver;

use EApp\Component\Module;
use EApp\Event\EventManager;
use EApp\ModuleCore;
use EApp\Prop;
use EApp\Result;
use EApp\SecurityFilter\ConfigFilter;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Json;
use EApp\System\Interfaces\SystemDriver;

class ConfigEditor implements SystemDriver
{
	/**
	 * @var ModuleCore
	 */
	protected $module_core;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	/**
	 * @var int
	 */
	protected $updated = 0;

	public function __construct()
	{
		$this->module_core = new ModuleCore();
		$this->module = $this->module_core;
	}

	public function getUpdatedCount()
	{
		return $this->updated;
	}

	public function getModule()
	{
		return $this->module;
	}

	public function set( $name, $value )
	{
		return $this->assign($name, $value);
	}

	public function setData( array $data )
	{
		$this->updated = 0;
		foreach( $data as $name => $value )
		{
			$this->assign($name, $value, false);
		}
		return $this;
	}

	public function setNull( $name )
	{
		return $this->assign( $name );
	}

	public function setNulls( array $names )
	{
		$this->updated = 0;
		foreach( $names as $name )
		{
			$this->assign($name, null, false);
		}
		return $this;
	}

	protected function assign( $name, $value = null, $updated = true )
	{
		if( $updated )
		{
			$this->updated = 0;
		}

		$row = \DB::table("config")
			->where("name", $name)
			->first(["id", "value", "type", "module_id"]);

		if( ! $row )
		{
			throw new NotFoundException("FileConfig name '{$name}' not found");
		}

		$m_id = (int) $row->module_id;
		$m_id > 0 && $this->reloadModule( new Module( $m_id, false ));

		try {

			$filter = new ConfigFilter($this->module);
			$type = $row->type;
			$filter_data = [];

			// email({"multiply":true})
			if( preg_match('/^(.*?)\((.*?)\)$/', $type, $m) )
			{
				$filter_data = strlen($m[2]) ? (array) Json::parse($m[1], true) : [];
				$type = $m[1];
			}

			$filter_data["name"] = $type;

			// filter value
			if( $value !== null )
			{
				$value = $filter->filter($value, $filter_data, $name);
			}

			$prop = new Prop([
				"value" => $value,
				"abort" => false
			]);

			// call listener
			$dispatcher = EventManager::dispatcher("onComponentDriverAction");
			$dispatcher
				->dispatch(
					new Events\ConfigAssignEvent($this, $name, $value),
					function($result) use ($prop) {
						if( $result instanceof Result )
						{
							if($result->success())
							{
								$prop->set("value", $prop->getOr("value", null));
							}
							else
							{
								$prop->set("abort", true);
							}
						}
					}
				);

			// set current value after listener modify
			$value = $prop->get("value");
			if($value === null)
			{
				$value = "";
			}
			else
			{
				$value = $filter->toDataBaseFormat($value, $filter_data, $name);
			}

			// update database value
			if( !$prop->get("abort") && $value !== $row->value )
			{
				\DB::table("config")
					->whereId( (int) $row->id )
					->update([
						"value" => $value
					]);
			}
		}
		catch(\Exception $e)
		{
			$m_id > 0 && $this->reloadModule( $this->module );
			throw $e;
		}

		$dispatcher->complete();
		$m_id > 0 && $this->reloadModule( $this->module );

		return $this;
	}

	protected function reloadModule( Module $module )
	{
		unset($this->module);
		$this->module = $module;
	}
}