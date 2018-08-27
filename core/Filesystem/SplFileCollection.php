<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 23:22
 */

namespace EApp\Filesystem;

use EApp\Support\Collection;
use EApp\Interfaces\TypeOfInterface;

class SplFileCollection extends Collection implements TypeOfInterface
{
	public function typeOf( & $value, $name = null ): bool
	{
		return is_null($name) && $value instanceof \SplFileInfo;
	}

	public function sortFileByTime()
	{
		return $this->sortFileBy("time");
	}

	public function sortFileByTimeDesc()
	{
		return $this->sortFileBy("time", true);
	}

	public function sortFileBySize()
	{
		return $this->sortFileBy("size");
	}

	public function sortFileBySizeDesc()
	{
		return $this->sortFileBy("size", true);
	}

	public function sortFileByName()
	{
		return $this->sortFileBy("filename");
	}

	public function sortFileByNameDesc()
	{
		return $this->sortFileBy("filename", true);
	}

	public function sortFileBy( string $name, bool $desc = false)
	{
		static $mask = [
			'name' => 'getFilename',
			'filename' => 'getFilename',
			'pathname' => 'getPathname',
			'path' => 'getPath',
			'basename' => 'getBasename',
			'extension' => 'getExtension',
			'type' => 'getType',
			'size' => 'getSize',
			'time' => 'getMTime',
			'mtime' => 'getMTime',
			'atime' => 'getATime',
			'ctime' => 'getCTime',
			'owner' => 'getOwner',
			'perms' => 'getPerms',
			'group' => 'getGroup',
		];

		$name = strtolower($name);
		if( !isset($mask[$name]) )
		{
			throw new \InvalidArgumentException("Invalid file sort name '{$name}'");
		}

		$items  = $this->items;
		$method = $mask[$name];

		if( $desc )
		{
			usort($items, function(\SplFileInfo $a, \SplFileInfo $b) use ($method) {
				return $a->{$method}() <=> $b->{$method}();
			});
		}
		else
		{
			usort($items, function(\SplFileInfo $a, \SplFileInfo $b) use ($method) {
				return $b->{$method}() <=> $a->{$method}();
			});
		}

		return new static($items);
	}
}