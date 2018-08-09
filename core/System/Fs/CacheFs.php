<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.07.2018
 * Time: 1:31
 */

namespace EApp\System\Fs;

use EApp\Support\Collection;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Fs\Traits\RelativePathTrait;
use EApp\System\Fs\Traits\UnlinkTrait;

class CacheFs implements Loggable
{
	use UnlinkTrait;
	use RelativePathTrait;
	use LoggableTrait;

	private $directory;

	private $valid = false;

	private $is_root = false;

	private $each = false;
	private $each_file;
	private $each_directory;
	private $each_recursive;
	private $each_match;
	private $each_reg_exp;

	const MODE_FILE = 1;
	const MODE_DIRECTORY = 2;

	public function __construct( string $directory = "" )
	{
		$this->directory = APP_DIR . "cache";

		$this->setRelativePath($directory);
		if(strlen($this->relative))
		{
			$this->directory .= DIRECTORY_SEPARATOR . $directory;
		}
		else
		{
			$this->is_root = true;
		}

		if( is_dir($this->directory) && ! is_link($this->directory) )
		{
			$this->valid = true;
		}
	}

	public function isExists()
	{
		return $this->valid;
	}

	public function each( \Closure $callback, int $mode = 0, bool $recursive = false )
	{
		return $this->eachMatch( $callback, "", $mode, $recursive );
	}

	public function eachMatch( \Closure $callback, string $reg_exp = "", int $mode = 0, bool $recursive = false )
	{
		if( $this->each )
		{
			(new CacheFs($this->relative))->eachMatch($callback, $reg_exp, $mode, $recursive);
		}

		else if( $this->valid )
		{
			$this->each = true;
			$this->each_file = $mode === self::MODE_FILE;
			$this->each_directory = $mode === self::MODE_DIRECTORY;
			$this->each_recursive = $recursive;
			$this->each_match = strlen($reg_exp) > 0;
			$this->each_reg_exp = $reg_exp;
			$this->eachClosure( $callback, $this->directory, $this->is_root );
			$this->each = false;
		}

		return $this;
	}

	public function ls( int $mode = 0, bool $recursive = false ): Collection
	{
		$collection = new Collection();
		$this->each( function(\SplFileInfo $file) use ($collection) { $collection[] = $file; }, $mode, $recursive );
		return $collection;
	}

	public function file( string $file )
	{
		if( $this->valid )
		{
			$file = $this->directory . DIRECTORY_SEPARATOR . $file;
			if( is_file($file) && ! is_link($file) )
			{
				return new \SplFileInfo($file);
			}
		}
		return false;
	}

	public function calculate()
	{
		$cache_info = new CacheCalculate();
		$this->each(function(\SplFileInfo $info) use ($cache_info) { $cache_info->add($info); }, self::MODE_FILE, true);
		return $cache_info;
	}

	public function clean( bool $recursive = false, bool $throw = false )
	{
		$n = 0;
		$this->each(function(\SplFileInfo $info) use($throw, & $n) { $this->unlink($info, $throw) && $n ++; }, self::MODE_FILE, $recursive);
		return $n;
	}

	public function cleanMatch( string $reg_exp, bool $recursive = false, bool $throw = false )
	{
		$n = 0;
		$this->eachMatch(function(\SplFileInfo $info) use($throw, & $n) { $this->unlink($info, $throw) && $n ++; }, $reg_exp, self::MODE_FILE, $recursive);
		return $n;
	}

	public function cleanFile( string $file, bool $throw = false )
	{
		$file = $this->file($file);
		if($file)
		{
			return $this->unlink( $file, $throw );
		}
		return true;
	}

	protected function eachClosure( \Closure $callback, string $path, bool $root = false )
	{
		$iterator = new \FilesystemIterator( $path );

		/** @var \SplFileInfo $file */
		foreach( $iterator as $file )
		{
			$name = $file->getFilename();
			if($root && $name[0] === ".")
			{
				// fixed system files
				continue;
			}

			$recursive = $this->each_recursive && ! $file->isLink() && $file->isDir();
			$file_path = $path . DIRECTORY_SEPARATOR . $name;

			if( $this->each_match && ! preg_match($this->each_reg_exp, $file->getPath()) ||
				$this->each_file && ! $file->isFile() ||
				$this->each_directory && ! $file->isDir() )
			{
				$recursive && $this->eachClosure( $callback, $file_path );
				continue;
			}

			$callback( $file );
			$recursive && $this->eachClosure( $callback, $file_path );
		}
	}
}
