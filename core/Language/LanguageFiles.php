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
	protected $i18 = [];

	protected $i18_default = false;

	protected function loadPackage( string $package_name )
	{
		$lines = Prop::file('language/' . $this->language . '/' . $package_name, $exists);

		if($exists && $package_name === "default")
		{
			$this->i18[$package_name] = array_filter(
					Prop::file('language/i18/' . $this->language),
					static function( $item ) {
						return $item instanceof \Closure;
					}
				);

			$this->i18_default = isset($this->i18['default']);
		}

		return $exists ? $lines : false;
	}

	public function i18( int $number, array $array ): string
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

	public function i18Invoke( int $number, string $name, array $replace = [] ): string
	{
		if( isset($this->i18[$name]) )
		{
			return $this->i18[$name]($number, $replace);
		}
		else
		{
			return $name;
		}
	}
}