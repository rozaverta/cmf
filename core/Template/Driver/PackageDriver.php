<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 14:59
 */

namespace EApp\Template\Driver;

use EApp\Component\Module;
use EApp\Prop;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\GetModuleComponent;
use EApp\Support\Traits\LoggableTrait;
use EApp\Support\Traits\Write;
use EApp\System\Interfaces\SystemDriver;
use EApp\Template\Scheme\PackageSchemeDesigner;
use ZipArchive;

class PackageDriver implements SystemDriver, Loggable
{
	use LoggableTrait;
	use GetModuleComponent;
	use Write;

	/**
	 * @var string
	 */
	protected $resource_dir;

	public function __construct( Module $module )
	{
		$this->setModule($module);
		$this->resource_dir = APP_DIR . "resources" . DIRECTORY_SEPARATOR . $module->getId() . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR;
	}

	// static

	/**
	 * @param $name
	 * @return \EApp\Template\Scheme\PackageSchemeDesigner|false
	 */
	public static function getPackageItem($name)
	{
		return \DB
			::table("template_packages")
				->where("name", $name)
				->limit(1)
				->setResultClass(PackageSchemeDesigner::class)
				->first();
	}

	// base

	public function install(string $name): Prop
	{
		$pg = self::getPackageItem($name);
		if($pg)
		{
			// todo error
		}

		$file = $this->getZipFile($name);
		//
	}

	public function update(string $name, bool $backup = false): Prop
	{
		$this->check($name);

		if( $backup )
		{
			$backup = $this
				->backup($name)
				->get("file_name");
		}

		$zip = $this->getZipFile($name);

		// todo check directory writable
		// todo unpack zip
		// todo remove old files
		// todo copy new files

		// todo dispatch event

		return new Prop([
			"name" => $name,
			"backup" => $backup
		]);
	}

	/**
	 * @param string $name
	 * @return Prop
	 * @throws NotFoundException
	 */
	public function backup(string $name): Prop
	{
		// check package module in database and module
		$this->check($name);

		// package directory
		$application_dir = APP_DIR . "view" . DIRECTORY_SEPARATOR . $name;
		$assets_dir = ASSETS_DIR . $name;

		if( ! is_dir($application_dir) && ! is_dir($assets_dir) )
		{
			throw new NotFoundException("The '{$name}' template package not found of the application/view or assets directory");
		}

		// file name
		$file_name = $name . "_" . date("Ymd_His") . ".zip";
		if( file_exists($file_name) )
		{
			throw new \RuntimeException("Can not create zip file, duplicate name");
		}

		// get temporary name
		$tmp_file = tempnam( sys_get_temp_dir(), "zip_" );
		if( !$tmp_file )
		{
			throw new \RuntimeException("Can not create zip file, create temporary file error");
		}

		// create and open zip file
		$zip = new ZipArchive;
		if( !$zip->open($tmp_file, ZipArchive::CREATE) === TRUE)
		{
			throw new \RuntimeException("Can not create zip file, open error");
		}

		function addZip( ZipArchive $zip, $dir, $suffix = "" )
		{
			if($suffix)
			{
				if( ! $zip->addEmptyDir($suffix) )
				{
					return false;
				}
				$suffix .= "/";
			}

			$iterator = new \FilesystemIterator( $dir );

			/** @var \SplFileInfo $file */
			foreach( $iterator as $file )
			{
				if( $file->isLink() )
				{
					continue;
				}

				if( $file->isDir() )
				{
					if( ! addZip($zip, $dir, $suffix . $file->getBasename() ) )
					{
						return false;
					}
				}
				else if( $file->isFile() )
				{
					if( ! $zip->addFile($file->getFilename(), $suffix . $file->getBasename()) )
					{
						return false;
					}
				}
			}

			return true;
		}

		// todo dispatch event

		if( is_dir($application_dir) && ! addZip($zip, $application_dir, "application") ||
			is_dir($assets_dir) && ! addZip($zip, $assets_dir, "assets") ||
			! $zip->close() )
		{
			throw new \RuntimeException("Can not create zip file, zip error");
		}

		if( ! rename($tmp_file, $file_name) )
		{
			throw new \RuntimeException("Can not create zip file, move (rename) error");
		}

		// todo add debug message

		return new Prop([
			"name" => $name,
			"file_name" => $file_name
		]);
	}

	public function restore(string $name, $date_time)
	{
		if( is_int($date_time) )
		{
			$date_time = date("Ymd_His", $date_time);
		}
		else if( $date_time instanceof \DateTime )
		{
			$date_time = $date_time->format("Ymd_His");
		}

		$file_name = $name . "_" . $date_time . ".zip";
		$file = $this->resource_dir . $file_name;
		if( !file_exists($file) )
		{
			throw new NotFoundException("The '{$file_name}' zip file not found");
		}

		$zip = new ZipArchive();
		if( !$zip->open($file) )
		{
			throw new \RuntimeException("Can not open zip file");
		}

		$this->reload($name, $zip);
	}

	public function uninstall(string $name, bool $backup = false): Prop
	{
		//
	}

	// protected

	protected function check(string $name)
	{
		// check package module in database and module
		$pg = self::getPackageItem($name);
		if( !$pg )
		{
			throw new NotFoundException("The '{$name}' template package not found");
		}

		if( $pg->module_id !== $this->getModule()->getId() )
		{
			throw new \InvalidArgumentException("The '{$name}' template package belongs to another module");
		}
	}

	protected function reload($name, ZipArchive $zip)
	{
		//
	}

	protected function getZipFile($name)
	{
		$file = $this->getModule()->getPath() . "resources" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $name . ".zip";
		if(!file_exists($file))
		{
			throw new NotFoundException("Package zip file '{$name}.zip' not found");
		}

		$zip = new ZipArchive();
		if( !$zip->open($file) )
		{
			throw new \RuntimeException("Can not open zip file");
		}

		return $zip;
	}
}