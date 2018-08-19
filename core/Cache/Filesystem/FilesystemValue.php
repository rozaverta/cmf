<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:54
 */

namespace EApp\Cache\Filesystem;

use EApp\Cache\KeyName;
use EApp\Cache\Value;
use EApp\Filesystem\Filesystem;
use EApp\Filesystem\WriteFileTrait;

class FilesystemValue extends Value
{
	use WriteFileTrait;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	private $file_path;

	private $life = 0;

	private $file_exists = false;

	public function __construct( Filesystem $filesystem, KeyName $key_name, string $directory = "cache" )
	{
		if( ! $key_name instanceof FilesystemKeyName )
		{
			throw new \InvalidArgumentException("You must used the " . FilesystemKeyName::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}

		parent::__construct($key_name);

		$this->filesystem = $filesystem;
		$this->file_path = APP_DIR . $directory . DIRECTORY_SEPARATOR . $key_name->getKey();
	}

	public function load( int $life = 0 )
	{
		$this->life = $life;
		$this->ready();
	}

	public function has(): bool
	{
		return $this->file_exists;
	}

	public function set( string $value ): bool
	{
		$value = str_replace( '?>', '', $value);
		$value = str_replace( '<?', '', $value);
		$value = '<' . "?php defined('ELS_CMS') || exit('Not access'); ob_start(); ?" . '>' . $value . '<' . "?php \$data = ob_get_contents(); ob_end_clean();";

		if( ! $this->writeFile($this->file_path, $value) )
		{
			return false;
		}

		$this->ready(false);
		return true;
	}

	protected function exportData( $data ): bool
	{
		if( ! $this->writeFileExport($this->file_path, $data) )
		{
			return false;
		}

		$this->ready(false);
		return true;
	}

	public function get()
	{
		return $this->has() ? $this->filesystem->getRequireData($this->file_path, "") : null;
	}

	public function import()
	{
		return $this->has() ? $this->filesystem->getRequireData($this->file_path, []) : null;
	}

	public function forget(): bool
	{
		$this->file_exists = false;

		if( $this->filesystem->isFile($this->file_path) )
		{
			return $this->filesystem->deleteOnce($this->file_path);
		}
		else
		{
			return true;
		}
	}

	protected function ready( $expired = true )
	{
		$this->file_exists = $this->filesystem->isFile($this->file_path);
		if($this->file_exists && $expired && $this->life > 0)
		{
			$time = $this->filesystem->lastModified($this->file_path);
			if( $time + $this->life < time() )
			{
				$this->forget();
			}
		}
	}
}