<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.07.2017
 * Time: 14:26
 */

namespace EApp\Component;

use EApp\Support\Collection;
use EApp\Support\Exceptions\ReadyException;
use EApp\Support\Str;
use EApp\System\Fs\FileResource;
use ReflectionClass;

abstract class ModuleConfig
{
	public $version = "1.0.0";

	public $name  = "";

	public $title = "";

	public $route = false;

	public $support = [];

	public $data = [];

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
			$data = $manifest->getAll();
			foreach(array_keys($data) as $key)
			{
				if( in_array($key, $this->props, true) )
				{
					$this->{$key} = $data[$key];
				}
				else
				{
					$this->data[$key] = $data[$key];
				}
			}
		}

		// ready module name

		if( ! $this->name && preg_match('|([^\\\\]+)\\\\[^\\\\]+$|', $this->reflector->getName(), $m))
		{
			$this->name = $m[1];
		}

		// create module title

		if( ! $this->title )
		{
			$this->title = $this->name . " module";
		}
	}

	/**
	 * @param $name
	 * @return \EApp\System\Fs\FileResource|null
	 */
	public function getResource( $name )
	{
		$file = $this->getPath() . "resources" . DIRECTORY_SEPARATOR . $name . ".json";
		if( !file_exists($file) )
		{
			return null;
		}

		return new FileResource($file);
	}

	/**
	 * Get all module resources
	 *
	 * @return Collection
	 * @throws ReadyException
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
				throw new ReadyException("Cannot ready resources directory for the '" . get_class($this) . "' module");
			}

			$path .= DIRECTORY_SEPARATOR;
			foreach( $scan as $file )
			{
				if( $file[0] !== "." && preg_match('/\.json$/', $file) )
				{
					$list[] = new FileResource($path . $file);
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

	public function getNameSpace()
	{
		return $this->reflector->getNamespaceName() . "\\";
	}
}