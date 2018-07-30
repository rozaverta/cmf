<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 1:45
 */

namespace EApp\Component\Driver\Tools;


use EApp\Support\Traits\Get;
use EApp\Support\Traits\Set;
use EApp\Support\Traits\Write;

class FileConfig
{
	use Write;
	use Set;
	use Get;

	protected $items = [];

	protected $file = "";

	protected $full_path = "";

	protected $name = "";

	public function __construct($name, $dir = null)
	{
		$this->name = $name;
		$this->file = APP_DIR . "config" . DIRECTORY_SEPARATOR;
		if($dir)
		{
			$dir = trim($dir, "/");
			if( DIRECTORY_SEPARATOR !== "/" )
			{
				$dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);
			}
			$this->file .= $dir . DIRECTORY_SEPARATOR;
		}
		$this->file .= $this->name . ".php";
	}

	public function ready()
	{
		$this->items = [];
		if( $this->fileExists() )
		{
			$this->items = \E\IncludeContentFile( $this->file );
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function getFileName()
	{
		return $this->file;
	}

	public function getPath()
	{
		return $this->full_path;
	}

	public function fileExists()
	{
		return file_exists($this->file) && is_file($this->file);
	}

	public function write()
	{
		if( !$this->makeDir($this->full_path) )
		{
			return false;
		}
		else
		{
			return $this->writeFileContent($this->file, $this->getAll(), false);
		}
	}
}