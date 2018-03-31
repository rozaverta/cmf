<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 0:52
 */

namespace EApp\Proto;

use EApp\App;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\Compare;

abstract class Plugin
{
	use Get;
	use Compare;

	protected $items = [];
	protected $cacheType = "nocache";
	protected $cacheData = [];

	public function __construct( $data )
	{
		if( ! is_array( $data ) )
		{
			return;
		}

		if( isset( $data["cache"] ) )
		{
			$cacheType = strtolower( trim( $data["cache"] ) );
			if( $cacheType === "page" || $cacheType === "plugin" || $cacheType === "view" )
			{
				$this->cacheType = $cacheType;
			}
			if( isset( $data["time"] ) && is_numeric( $data["time"] ) )
			{
				$this->cacheData["time"] = (int) $data["time"];
			}
			unset( $data["cache"], $data["time"] );
		}

		if( ! isset($data["tpl"]) )
		{
			$tpl = str_replace( "\\", '.', get_class($this) );
			$tpl = strtolower( trim($tpl, ".") );
			if( substr( $tpl, 0, 7 ) == 'plugin.' )
			{
				$tpl = substr( $tpl, 7 );
			}
			$data["tpl"] = $tpl;
		}

		if( method_exists( $this, "filterData" ) )
		{
			$cache = $this->filterData( $data );
			if( is_array( $cache ) )
			{
				foreach( $cache as $key => $value )
				{
					$this->cacheValue( $key, $value );
				}
			}
		}
		else
		{
			$this->items = $data;
		}
	}

	abstract public function getContent();

	public function cacheType()
	{
		return $this->cacheType;
	}

	public function cacheData()
	{
		return $this->cacheData;
	}

	protected function render( array $data )
	{
		return App::View()->getTpl( $this->get("tpl"), $data );
	}

	protected function cacheValue( $name, $value )
	{
		$name = strtolower(trim($name));
		if( $name !== "directory" && $name !== "time" )
		{
			$this->cacheData[$name] = $value;
		}
	}
}
