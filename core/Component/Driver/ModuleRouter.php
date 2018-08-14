<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Component\Module;
use EApp\Component\Scheme\RouteSchemeDesigner;
use EApp\Database\Query\Builder;
use EApp\Event\EventManager;
use EApp\Prop;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Json;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;
use EApp\Text;

class ModuleRouter implements SystemDriver, Loggable
{
	use LoggableTrait;

	protected $types = [
		"index", "all", "404", "match", "uri", "of", "path", "query", "host"
	];

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	public function __construct( Module $module )
	{
		if($module->getId() === 0)
		{
			throw new \InvalidArgumentException("You can not use CoreModule for router");
		}

		$this->module = $module;
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function add( $path, $type = null, $position = null, array $properties = null )
	{
		$path = $this->createPath($path, $type);
		$position = is_int($position) && $position > 0 ? $position : $this->getNextPosition();
		if( !is_array($properties) )
		{
			$properties = [];
		}

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\RouterAddDriverEvent($this, $this->getEvn($path, $position, $properties))
			);

		$id = \DB::table("module_router")->insertGetId([
			"module_id" => $this->module->getId(),
			"path" => $path,
			"properties" => Json::stringify($properties),
			"position" => $position
		]);

		$dispatcher->complete($id);
		$this->addLogError(new Text("Add new mount point: %s", $path), "DEBUG");

		return $this;
	}

	public function edit( $id, $path, $type = null, $position = null, array $properties = null )
	{
		$old = $this->getRouteData($id);
		$id = $old["id"];
		$path = $this->createPath($path, $type);
		$position = is_int($position) && $position > 0 ? $position : $old["position"];
		if( !is_array($properties) )
		{
			$properties = $old["properties"];
		}

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\RouterEditDriverEvent($this, $this->getEvn($path, $position, $properties, $id))
			);

		\DB::table("module_router")
			->whereId($id)
			->update([
				"path" => $path,
				"properties" => Json::stringify($properties),
				"position" => $position
			]);

		$dispatcher->complete();
		$this->addLogError(new Text("Edit mount point: %s", $path), "DEBUG");

		return $this;
	}

	public function delete( $id )
	{
		$old = $this->getRouteData($id);
		$id = $old["id"];
		$path = $old["path"];

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\RouterDeleteDriverEvent($this, $this->getEvn($path, $old["position"], $old["properties"], $id))
			);

		\DB::table("module_router")
			->whereId($id)
			->delete();

		$dispatcher->complete();
		$this->addLogError(new Text("Delete mount point: %s", $path), "DEBUG");

		return $this;
	}

	protected function getRouteData( $id )
	{
		$row = \DB::table("module_router")
			->whereId($id)
			->first();

		if( !$row )
		{
			return false;
		}

		$m_id = (int) $row->module_id;
		if($m_id !== $this->module->getId())
		{
			throw new \InvalidArgumentException("You can not modify the mount point, because the used module does not match the mount point module");
		}

		return [
			"id" => (int) $row->id,
			"module_id" => $m_id,
			"path" => $row->path,
			"position" => (int) $row->position,
			"properties" => strlen($row->properties) ? Json::parse($row->properties, true) : [],
			"properties_json" => $row->properties
		];
	}

	protected function getEvn( $path, $position, $properties, $id = 0 )
	{
		$evn = [
			"type" => "path",
			"path" => $path,
			"position" => $position,
			"module" => $this->getModule(),
			"properties" => $properties
		];

		if($id > 0)
		{
			$evn["id"] = $id;
		}

		if($path[0] === "@")
		{
			$end = strpos($path, ":");
			if($end !== false)
			{
				$evn["type"] = substr($path, 1, $end - 2);
				$path = substr($path, $end);
				if( $evn["type"] === "query" )
				{
					$evn["path"] = [];
					parse_str($path, $evn["path"]);
				}
				else
				{
					$evn["path"] = $path;
				}
			}
			else
			{
				$evn["type"] = substr($path, 1);
				$evn["path"] = "";
			}
		}

		return $evn;
	}

	protected function getNextPosition()
	{
		$max = \DB::table("module_router")->max("position");
		if(!$max)
		{
			return 1;
		}
		else
		{
			return $max + 1;
		}
	}

	protected function createPath( $path, $type )
	{
		if( !$type )
		{
			$type = "path";
		}
		else
		{
			$type = strtolower($type);
		}

		if( !in_array($type, $this->types, true) )
		{
			throw new \InvalidArgumentException("Unknown mount type '{$type}'");
		}

		if( $type === "query" && is_array($path) )
		{
			$path = http_build_query($path);
		}
		else
		{
			$path = trim($path);
		}

		if( $type === "index" || $type === "all" || $type === "404" )
		{
			if($path)
			{
				throw new \InvalidArgumentException("You can not use the path value for '{$type}' type");
			}

			return '@' . $type;
		}

		if($type === "path")
		{
			return !strlen($path) || $path === "/" ? "@all" : $path;
		}

		if( !strlen($path) )
		{
			throw new \InvalidArgumentException("You must use path value for '{$type}' type");
		}

		if( $type === "host" && ! filter_var($path, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
		{
			throw new \InvalidArgumentException("Invalid domain name '{$path}' for host type");
		}

		return '@' . $type . ':' . $path;
	}

	// static methods

	public static function found( array $conf = null )
	{
		$builder = \DB::table("module_router");

		if( !is_null($conf) && count($conf) )
		{
			$prop = new Prop($conf);

			$prop->getIs("module_id") && $builder->where("module_id", $prop->get("module_id"));
			$prop->getIs("module_ids") && $builder->whereIn("module_id", (array) $prop->get("module_ids"));
			$prop->getIs("id") && $builder->whereId($prop->get("id"));
			$prop->getIs("ids") && $builder->whereIn("id", (array) $prop->get("ids"));

			if($prop->getIs("type"))
			{
				$type = strtolower($prop->get("type"));
				$path = $prop->getIs("path") ? $prop->get("path") : null;

				if($type === "path")
				{
					if(is_null($path))
					{
						$builder->where("path", "not like", '@%');
					}
					else
					{
						$builder->where("path", $path);
					}
				}
				else
				{
					if(is_null($path))
					{
						$builder->where("path", "like", '@' . $type . '%');
					}
					else if( $type === "all" || $type === "index" || $type === "404" )
					{
						$builder->where("path", '@' . $type);
					}
					else
					{
						if($type === "query" && is_array($path))
						{
							$path = http_build_query($path);
						}
						else
						{
							$path = trim($path);
						}
						$builder->where("path", '@' . $type . ':' . $path);
					}
				}
			}
			else if($prop->getIs("path"))
			{
				$builder->where("path", $prop->get("path"));
			}
		}

		return self::getSqlResult($builder);
	}

	public static function getRouteItem( $id )
	{
		$result = self::getSqlResult(
			\DB::table("module_router")->whereId($id)
		);

		return count($result) ? reset($result) : false;
	}

	protected static function getSqlResult( Builder $builder )
	{
		$sql = $builder
			->select(["*"])
			->toSql();

		return \DB::connection()->select($sql, [], true, RouteSchemeDesigner::class);
	}
}