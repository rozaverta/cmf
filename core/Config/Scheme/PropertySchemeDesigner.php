<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 1:20
 */

namespace EApp\Config\Scheme;

use EApp\App;
use EApp\Component\Module;
use EApp\ModuleCore;
use EApp\SecurityFilter\ConfigFilter;
use EApp\Support\Json;

class PropertySchemeDesigner
{
	public $name;

	public $type;

	public $value;

	public $raw_value;

	public $default_value;

	public $module_id;

	protected $cache_value = null;

	protected $cache_data;

	protected $filter = [];

	protected $is_null = false;

	public function __construct()
	{
		$this->module_id = (int) $this->module_id;
		$this->value = trim($this->value);
		$this->raw_value = $this->value;

		if( !strlen($this->value) )
		{
			$this->value = trim($this->default_value);

			if( !strlen($this->value) )
			{
				$this->is_null = true;
			}
		}

		if( preg_match('/^(.*?)\((.*?)\)$/', $this->type, $m) )
		{
			$this->type = $m[1];
			$this->filter = $this->parseJson($m[2], "filter properties");
		}

		$this->filter["name"] = $this->type;
	}

	public function isNull()
	{
		return $this->is_null;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function getValue()
	{
		if( $this->value !== $this->cache_value )
		{
			$filter = new ConfigFilter( $this->module_id > 0 ? Module::cache($this->module_id) : new ModuleCore() );
			$this->cache_value = $this->value;
			$this->cache_data = $filter->filter($this->cache_value, $this->filter, $this->name);
		}

		return $this->cache_data;
	}

	public function getTextValue()
	{
		return $this->value;
	}

	protected function parseJson( $value, $type )
	{
		$result = null;

		try {
			$result = Json::parse( $value, true );
		}
		catch( \InvalidArgumentException $e ) {
			App::Log("Cannot ready json config {$type} '{$this->name}'");
			App::Log($e);
		}

		return is_array($result) ? $result : [];
	}
}