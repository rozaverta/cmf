<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:16
 */

namespace EApp\Cache\Filesystem;

use EApp\Cache\CacheStoreInterface;
use EApp\Cache\CacheValueInterface;
use EApp\Cache\KeyName;
use EApp\Filesystem\Filesystem;

class FilesystemStore implements CacheStoreInterface
{
	protected $filesystem;

	protected $directory = "cache";

	protected $life = 0;

	public function __construct( Filesystem $filesystem, string $directory = "", int $life = 0 )
	{
		$this->filesystem = $filesystem;

		$directory = trim($directory, "/");
		if( strlen($directory) )
		{
			$this->directory = $directory;
		}

		if( $life > 0 )
		{
			$this->life = $life;
		}
	}

	public function getValue( KeyName $key_name, int $life = null ): CacheValueInterface
	{
		$value = new FilesystemValue($this->filesystem, $key_name, $this->directory);
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		// TODO: Implement flush() method.
	}

	public function getKeyName( string $key_name, string $prefix = "", array $properties = [] ): KeyName
	{
		return new FilesystemKeyName($key_name, $prefix, $properties);
	}
}