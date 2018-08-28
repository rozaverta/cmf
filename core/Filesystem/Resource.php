<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.01.2018
 * Time: 5:13
 */

namespace EApp\Filesystem;

use EApp\Module\Module;
use EApp\Filesystem\Exceptions\AccessFileException;
use EApp\Filesystem\Exceptions\NotFoundFileException;
use EApp\Filesystem\Exceptions\ReadFileException;
use EApp\Helper;
use EApp\ModuleCore;
use EApp\Exceptions\NotFoundException;
use EApp\Support\Json;
use EApp\Traits\GetTrait;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Interfaces\ModuleComponentInterface;

class Resource implements ModuleComponentInterface
{
	use GetTrait;
	use GetModuleComponentTrait;

	protected $pathname;
	protected $ready = false;
	protected $items = [];
	protected $type  = 'unknown';
	protected $name  = '';
	protected $path  = '';
	protected $raw   = '{}';

	/**
	 * Resource constructor.
	 *
	 * @param string $file
	 * @param string|null $directory
	 * @param Module|null $module
	 * @param bool|string $module_cache_version
	 * @throws NotFoundException
	 */
	public function __construct( $file, $directory = null, Module $module = null, $module_cache_version = false )
	{
		$dot = strrpos($file, '.');
		if( $dot === false )
		{
			$file .= '.json';
		}
		else if( strtolower(substr($file, $dot)) !== '.json' )
		{
			throw new \InvalidArgumentException("The resource must be a json data file");
		}

		if($module)
		{
			$this->setModule($module);
		}

		if( $file[0] === '@' )
		{
			$data = [];
			if($this->hasModule())
			{
				$data["module"] = $this->getModule()->getPath();
			}
			$file = Helper::path($file, ".json", $data);
		}
		else
		{
			if($this->hasModule())
			{
				if($module_cache_version)
				{
					$directory = APP_DIR . "resources" . DIRECTORY_SEPARATOR . $this->getModuleId() . DIRECTORY_SEPARATOR;
					if(is_string($module_cache_version) || is_int($module_cache_version))
					{
						$directory .= $module_cache_version . DIRECTORY_SEPARATOR;
					}
				}
				else
				{
					if($module instanceof ModuleCore)
					{
						$directory = CORE_DIR;
					}
					else
					{
						$directory = $this->getModule()->getPath();
					}
					$directory .= "resources" . DIRECTORY_SEPARATOR;
				}
			}
			else if($directory)
			{
				$directory = rtrim($directory, "/\\") . DIRECTORY_SEPARATOR;
			}

			if($directory)
			{
				$file = $directory . $file;
			}
		}

		$file = new \SplFileInfo(realpath($file));
		$this->pathname = $file->getPathname();

		if( ! $file->isFile() )
		{
			throw new NotFoundFileException($this->pathname,"The resource file not found");
		}

		$this->path = $file->getPath();
		$this->name = $file->getBasename(".json");
	}

	/**
	 * Load resource content data
	 *
	 * @return $this
	 * @throws AccessFileException
	 * @throws ReadFileException
	 */
	public function ready()
	{
		if( $this->ready )
		{
			return $this;
		}

		$this->raw = @ file_get_contents($this->pathname);
		if( !$this->raw )
		{
			throw new AccessFileException($this->pathname,"Cannot ready resource '{$this->name}'");
		}

		try {
			$data = Json::parse($this->raw, true);
			if( ! is_array($data) )
			{
				throw new \InvalidArgumentException("Resource data is not array");
			}
		}
		catch( \InvalidArgumentException $e ) {
			throw new ReadFileException($this->pathname,"Cannot read resource '{$this->name}', json parser error: " . $e->getCode());
		}

		if( isset($data['type']) && is_string($data['type']) )
		{
			$this->type = $data['type'];
		}

		$this->ready = true;
		$this->items = $data;

		return $this;
	}

	/**
	 * Get resource type
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->ready()->type;
	}

	/**
	 * Compare resource type
	 *
	 * @param string $type
	 * @return bool
	 */
	public function hasType( string $type ): bool
	{
		if( substr($type, 0, 2) !== "#/" )
		{
			$type = "#/{$type}";
		}
		return $this->getType() === $type;
	}

	/**
	 * Get the resource file path without filename
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get the resource name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the path to the resource file
	 *
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->pathname;
	}

	/**
	 * Get raw content
	 *
	 * @return string
	 */
	public function getRawData(): string
	{
		return $this->ready()->raw;
	}
}