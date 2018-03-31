<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 22:59
 */

namespace EApp\Language;

use EApp\App;
use EApp\Text;

abstract class Language
{
	public $keys = [];

	public $lines = [];

	protected $packages = [];

	protected $language;

	public function __construct( $language )
	{
		$this->language = $language;
		$this->loadDefaultPackage();
	}

	abstract function loadPackage( $package_name );

	abstract function loadDefaultPackage();

	public function packages()
	{
		return $this->packages;
	}

	public function isDefault()
	{
		return $this->language == 'en';
	}

	public function loadIs( $package_name )
	{
		return in_array($package_name, $this->packages, true);
	}

	public function load( $package_name )
	{
		if( $this->loadIs($package_name) )
		{
			return true;
		}

		if( ! $this->loadPackage($package_name) )
		{
			App::Log( new Text("Cannot load language package %s", $package_name) );
			return false;
		}

		$this->packages[] = $package_name;
		return true;
	}
}