<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:23
 */

namespace EApp\Cache\File;

use EApp\Cache\Hash;

class FileHash extends Hash
{
	protected $delimiter = DIRECTORY_SEPARATOR;

	protected $file_name;

	protected $file_prefix;

	public function getHash(): string
	{
		return parent::getHash() . ".php";
	}

	public function keyName(): string
	{
		if( isset($this->file_name) )
		{
			return $this->file_name;
		}

		$file = $this->name;
		if( ! $this->validFileName($file) )
		{
			$file = "md5_" . md5($file);
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
				$name = "md5_" . md5($name);
			}

			$file .= DIRECTORY_SEPARATOR . $name;
		}

		$this->file_name = $file;
		return $file;
	}

	public function keyPrefix(): string
	{
		if( isset($this->file_prefix) )
		{
			return $this->file_prefix;
		}

		$prefix = trim($this->prefix, "/");
		if( strlen($prefix) > 0 )
		{
			$path = [];
			foreach(explode("/", $prefix) as $directory)
			{
				$path[] = $this->validFileName($directory) ? $directory : "md5_" . md5($directory);
			}
			$prefix = implode(DIRECTORY_SEPARATOR, $path);
		}

		$this->file_prefix = $prefix;
		return $prefix;
	}

	private function validFileName($name)
	{
		$len = strlen($name);
		return $len > 0 && $len <= 64 && ! preg_match('/[^a-zA-Z0-9_\-]/', $name);
	}
}