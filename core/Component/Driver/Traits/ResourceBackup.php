<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.04.2018
 * Time: 0:19
 */

namespace EApp\Component\Driver\Traits;

use EApp\App;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Interfaces\Jsonable;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Json;
use EApp\Support\Traits\Write;
use EApp\System\Files\FileResource;
use EApp\Text;

trait ResourceBackup
{
	use Write;

	protected function resourceRemoveFile( $name, $module_id = 0, $type = null )
	{
		$name = $this->getResourceFileName($name, $type);
		$file = $this->getResourceDir($module_id, false) . $name . ".json";
		if(file_exists($file) && is_file($file))
		{
			@ unlink($file) || App::Log()->lastPhp();
		}
	}

	protected function resourceDirIsWritable( $module_id, $version = false, $throw = false )
	{
		$path = rtrim($this->getResourceDir($module_id, false), DIRECTORY_SEPARATOR);
		$test = $this->makeDir($path) && $this->dirIsWritable($path, true);

		if($test && $version)
		{
			$path .= DIRECTORY_SEPARATOR . 'v_' . $version;
			if( !$this->makeDir($path) )
			{
				$test = false;
			}
		}

		if($test)
		{
			return true;
		}

		if($throw)
		{
			throw new \InvalidArgumentException("Can not create the resource version file, the directory is not writable");
		}

		return false;
	}

	protected function resourceWriteFileContent( $name, $module_id, $content, $version = false )
	{
		$type = null;

		if( $content instanceof FileResource )
		{
			$type = $content->getType();
			$text = $content->getRawData();
		}
		else
		{
			if( $content instanceof Arrayable )
			{
				$content = $content->toArray();
			}

			if( is_array($content) )
			{
				$text = Json::stringify($content);
				if( isset($content["type"]) )
				{
					$type = $content["type"];
				}
			}
			else if( $content instanceof Jsonable )
			{
				$text = $content->toJson();
			}
			else
			{
				$text = (string) $content;
			}
		}

		$dir = $this->getResourceDir($module_id, $version);
		$name = $this->getResourceFileName($name, $type);

		if( $this->makeDir(rtrim($dir, DIRECTORY_SEPARATOR)) && $this->writeFileContent($dir . $name, $text, false) )
		{
			return true;
		}

		if( $this instanceof Loggable )
		{
			$this->addLogText(new Text("Failure backup file %s", $name));
		}

		return false;
	}

	private function getResourceFileName($name, $type)
	{
		if( $type === "#/data_base_table" && substr($name, 0, 3) !== "db_" )
		{
			$name = "db_" . $name;
		}

		$dot = stripos($name, ".");
		if($dot === false || substr($name, $dot) !== ".json")
		{
			$name .= ".json";
		}

		return $name;
	}
	
	private function getResourceDir( $module_id, $version )
	{
		$path = APP_DIR . "resources" . DIRECTORY_SEPARATOR;
		if($module_id > 0)
		{
			$path .= $module_id . DIRECTORY_SEPARATOR;
		}
		if($version)
		{
			$path .= 'v_' . $version . DIRECTORY_SEPARATOR;
		}
		return $path;
	}
}