<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.07.2017
 * Time: 14:26
 */

namespace EApp\Module;

use EApp\Support\Collection;
use EApp\Exceptions\ReadException;
use EApp\Support\Str;
use EApp\Filesystem\Resource;
use ReflectionClass;

abstract class ModuleConfig
{
	public $version = "1.0.0";

	public $name = "";

	public $title = "";

	public $route = false;

	public $support = [];

	public $extra = [];

	/**
	 * @var ReflectionClass
	 */
	private $reflector;

	private $props = ["name", "title", "version", "support", "route"];

	public function __construct()
	{
		$this->reflector = new ReflectionClass($this);

		// ready manifest json file

		$manifest = $this->getResource('manifest');

		if( $manifest )
		{
			$manifest->ready();
			$data = $manifest->getAll();
			foreach(array_keys($data) as $key)
			{
				if( in_array($key, $this->props, true) )
				{
					$this->{$key} = $data[$key];
				}
				else if( $key !== "type" )
				{
					$this->extra[$key] = $data[$key];
				}
			}
		}

		// ready module name

		if( ! $this->name )
		{
			$name_space = $this->reflector->getNamespaceName();
			if( $name_space )
			{
				$end = strrpos($name_space, "\\");
				$this->name = $end === false ? $name_space : substr($name_space, $end + 1);
			}
		}

		// create module title

		if( ! $this->title )
		{
			$this->title = $this->name . " module";
		}
	}

	/**
	 * @param $name
	 * @return \EApp\Filesystem\Resource|null
	 */
	public function getResource( $name )
	{
		$file = $this->getPath() . "resources" . DIRECTORY_SEPARATOR . $name . ".json";
		if( !file_exists($file) )
		{
			return null;
		}

		return new Resource($file);
	}

	/**
	 * Get all module resources
	 *
	 * @return Collection
	 * @throws ReadException
	 */
	public function listResources()
	{
		$list = [];
		$path = $this->getPath() . "resources";
		if( file_exists($path) )
		{
			$scan = @ scandir($path);
			if( !$scan )
			{
				throw new ReadException("Cannot ready resources directory for the '" . get_class($this) . "' module");
			}

			$path .= DIRECTORY_SEPARATOR;
			foreach( $scan as $file )
			{
				if( $file[0] !== "." && preg_match('/\.json$/', $file) )
				{
					$list[] = new Resource($path . $file);
				}
			}
		}

		return new Collection($list);
	}

	public function getPath()
	{
		return dirname($this->reflector->getFileName()) . DIRECTORY_SEPARATOR;
	}

	public function getKey()
	{
		return Str::cache($this->name, "snake");
	}

	public function getNamespace()
	{
		return $this->reflector->getNamespaceName() . "\\";
	}
}