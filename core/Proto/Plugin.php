<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 0:52
 */

namespace EApp\Proto;

use EApp\CI\View;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\Compare;
use EApp\Plugin\Interfaces\PluginPrepareProperties;

abstract class Plugin
{
	use Get;
	use Compare;

	protected $items = [];

	protected $cacheType = "nocache";

	protected $cacheData = [];

	protected $view;

	public function __construct( $data, View $view )
	{
		$this->view = $view;

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

		if( $this instanceof PluginPrepareProperties )
		{
			$cache = $this->prepareProperties( $data );
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

	public function getTplName()
	{
		$tpl = $this->get("tpl");

		if(!$tpl)
		{
			$tpl = str_replace( "\\", '.', get_class($this) );
			$tpl = strtolower( trim($tpl, ".") );
			if( substr( $tpl, 0, 7 ) == 'plugin.' )
			{
				$tpl = substr( $tpl, 7 );
			}
			$this->items["tpl"] = $tpl;
		}

		return $tpl;
	}

	protected function render( array $data )
	{
		return $this->view->getTpl( $this->getTplName(), $data );
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
