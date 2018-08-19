<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.01.2018
 * Time: 5:13
 */

namespace EApp\System\Fs;

use EApp\Component\Module;
use EApp\Helper;
use EApp\ModuleCore;
use EApp\Support\Exceptions\FileReadyException;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Exceptions\ReadyException;
use EApp\Support\Json;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\GetModuleComponent;
use EApp\System\Interfaces\ModuleComponent;

class FileResource implements ModuleComponent
{
	use Get;
	use GetModuleComponent;

	protected $file;
	protected $ready = false;
	protected $items = [];
	protected $type  = 'unknown';
	protected $name  = '';
	protected $path  = '';
	protected $raw   = '{}';

	/**
	 * FileResource constructor.
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
			throw new \InvalidArgumentException("The resource must be a json data file.");
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
				$data["module_resources"] = $this->getModule()->getPath() . "resources" . DIRECTORY_SEPARATOR;
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

		$file = realpath($file);
		if( ! is_file($file) )
		{
			throw new NotFoundException("The resource file not found.");
		}

		if( DIRECTORY_SEPARATOR !== '/' && strpos($file, '/') !== false )
		{
			$file = str_replace('/', DIRECTORY_SEPARATOR, $file );
		}

		$end = strrpos($file, DIRECTORY_SEPARATOR);
		$dot = strrpos($file, '.');
		$this->file = $file;
		$this->path = $end === false ? "" : substr($file, 0, $end + 1);
		$this->name = $end === false ? substr($file, 0, $dot) : substr($file, $end + 1, $dot - $end - 1);
	}

	public function ready()
	{
		if( $this->ready )
		{
			return $this;
		}

		$this->raw = @ file_get_contents($this->file);
		if( !$this->raw )
		{
			throw new FileReadyException("Cannot ready resource '{$this->name}'");
		}

		try {
			$data = Json::parse($this->raw, true);
			if( ! is_array($data) )
			{
				throw new \InvalidArgumentException();
			}
		}
		catch( \InvalidArgumentException $e ) {
			throw new ReadyException("Cannot ready resource '{$this->name}', json parser error " . $e->getCode());
		}

		if( isset($data['type']) && is_string($data['type']) )
		{
			$this->type = $data['type'];
		}

		$this->ready = true;
		$this->items = $data;

		return $this;
	}

	public function getType()
	{
		return $this->ready()->type;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function getRawData()
	{
		return $this->ready()->raw;
	}
}