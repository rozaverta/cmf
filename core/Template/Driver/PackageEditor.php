<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 14:59
 */

namespace EApp\Template\Driver;


use EApp\System\Interfaces\SystemDriver;
use EApp\Template\Package;

class PackageEditor implements SystemDriver
{
	protected $id;

	protected $name;

	protected $assets_path;

	protected $view_path;

	protected $unix = false;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	public function __construct( Package $package )
	{
		$this->id = $package->get("id");
		$this->name = $package->get("name");
		$this->assets_path = $package->get("assets_path");
		$this->view_path = $package->get("view_path");
		$this->module = $package->getModule();
		$this->unix = DIRECTORY_SEPARATOR === "/";
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function rename( $new_name )
	{
		//
	}

	public function addAssetsFile($file)
	{
		//
	}

	public function addApplicationFile($file)
	{
		//
	}

	public function addTpl($file)
	{
		//
	}

	public function editAssetsFile($file)
	{
		//
	}

	public function editApplicationFile($file)
	{
		//
	}

	public function editTpl($file)
	{
		//
	}

	public function dropAssetsFile($file)
	{
		//
	}

	public function dropApplicationFile($file)
	{
		//
	}

	public function dropTpl($file)
	{
		//
	}

	public function moveAssetsFile($file)
	{
		//
	}

	public function moveApplicationFile($file)
	{
		//
	}

	public function moveTpl($file)
	{
		//
	}

	public function tplExists( $name )
	{
		return $this->fileExists( str_replace(".", "/", $name) . ".php", false );
	}

	public function assetsFileExists( $file )
	{
		return $this->fileExists( $file, true );
	}

	public function applicationFileExists( $file )
	{
		return $this->fileExists( $file, false );
	}

	protected function fileExists( $file, $assets )
	{
		if( !$this->unix )
		{
			$file = str_replace("/", DIRECTORY_SEPARATOR, $file );
		}
		$file = ltrim($file, DIRECTORY_SEPARATOR);
		$file = ($assets ? $this->assets_path : $this->view_path) . $file;
		return file_exists($file) && is_file($file);
	}
}