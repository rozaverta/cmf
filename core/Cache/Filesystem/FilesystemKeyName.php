<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:23
 */

namespace EApp\Cache\Filesystem;

use EApp\Cache\KeyName;

class FilesystemKeyName extends KeyName
{
	public function getKey(): string
	{
		return parent::getKey() . ".php";
	}

	public function keyName(): string
	{
		$file = $this->name;
		if( ! $this->validFileName($file) )
		{
			$file = md5($file);
		}

		if( count($this->data) )
		{
			$name = [];
			foreach( $this->data as $key => $value )
			{
				$name[] = $key . '-' . $value;
			}

			$name = implode('_', $name);
			if( ! $this->validFileName($name) )
			{
				$name = md5($name);
			}

			$file .= DIRECTORY_SEPARATOR . $name;
		}

		return $file;
	}

	public function keyPrefix(): string
	{
		$prefix = trim($this->prefix, "/");
		if( strlen($prefix) > 0 )
		{
			$path = [];
			foreach(explode("/", $prefix) as $directory)
			{
				$path[] = $this->validFileName($directory) ? $directory : md5($directory);
			}
			$prefix = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
		}

		return $prefix;
	}

	private function validFileName($name)
	{
		$len = strlen($name);
		return $len > 0 && $len <= 64 && ! preg_match('/[^a-zA-Z0-9_\-]/', $name);
	}
}