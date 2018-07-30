<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 14:59
 */

namespace EApp\Template\Driver;


use EApp\Component\Module;

use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;
use EApp\Template\Scheme\PackageSchemeDesigner;
use ZipArchive;

class PackageFactory implements SystemDriver, Loggable
{
	use LoggableTrait;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	/**
	 * @var string
	 */
	protected $resource_dir;

	public function __construct( Module $module )
	{
		$this->module = $module;
		$this->resource_dir = APP_DIR . "resources" . DIRECTORY_SEPARATOR . $this->module->getId() . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR;
	}

	// static

	/**
	 * @param $name
	 * @return \EApp\Template\Scheme\PackageSchemeDesigner|false
	 */
	public static function getPackageItem($name)
	{
		return \DB::table("template_packages")
			->where("name", $name)
			->limit(1)
			->setResultClass(PackageSchemeDesigner::class)
			->first();
	}

	// base

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function install($name)
	{
		$file = $this->getZipFile($name);
		//
	}

	public function update($name, $backup = false)
	{
		$file = $this->getZipFile($name);
		//
	}

	public function backup($name)
	{
		//
		// create zip file -> {$name}_{$date}.zip, $date -> Ymd_His
	}

	public function restore($name, $date_time)
	{
		if( is_int($date_time) )
		{
			$date_time = date("Ymd_His", $date_time);
		}

		$file_name = $name . "_" . $date_time . ".zip";
		$file = $this->resource_dir . $file_name;
		if( !file_exists($file) )
		{
			throw new NotFoundException("Zip file '{$file_name}' not found");
		}

		$zip = new ZipArchive();
		if( !$zip->open($file) )
		{
			throw new \Exception("Can not open zip file");
		}

		$this->reload($name, $zip);
	}

	public function delete($name)
	{
		//
	}

	// protected

	protected function reload($name, ZipArchive $zip)
	{
		//
	}

	protected function getZipFile($name)
	{
		$file = $this->module->get("path") . "resources" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $name . ".zip";
		if(!file_exists($file))
		{
			throw new NotFoundException("Package zip file '{$name}.zip' not found");
		}

		$zip = new ZipArchive();
		if( !$zip->open($file) )
		{
			throw new \Exception("Can not open zip file");
		}

		return $zip;
	}
}