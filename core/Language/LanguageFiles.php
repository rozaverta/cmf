<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 22:59
 */

namespace EApp\Language;

use EApp\Language\Interfaces\I18Interface;
use EApp\Prop;

class LanguageFiles extends Language implements I18Interface
{
	public $keys = [];

	public $lines = [];

	protected $i18 = [];

	protected $i18_default = false;

	protected $packages = [];

	function loadDefaultPackage()
	{
		$this->lines = Prop::file('language/' . $this->language);
		$this->i18 = Prop::file('language/i18/' . $this->language);
		$this->i18_default = isset($this->i18['default']);
	}

	function loadPackage( $package_name )
	{
		$lines = Prop::file('language/' . $this->language . '/' . $package_name);
		if(!count($lines))
		{
			return false;
		}

		$this->lines += $lines;
		return true;
	}

	public function i18( $number, array $array )
	{
		if( $this->i18_default )
		{
			return $this->i18['default']( $number, $array );
		}

		if( $number < 2 )
		{
			return $array[0];
		}
		else
		{
			return $array[1];
		}
	}

	public function i18Invoke( $key, $number )
	{
		if( isset($this->i18[$key]) )
		{
			return $this->i18[$key]($number);
		}
		else
		{
			return $key;
		}
	}
}