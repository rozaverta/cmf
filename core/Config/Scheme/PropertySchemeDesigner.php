<?php

namespace EApp\Config\Scheme;
use EApp\App;
use EApp\Support\Json;

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 1:20
 */
class PropertySchemeDesigner
{
	public $name;
	public $type;
	public $value;
	public $default_value;

	protected $is_null = false;

	public function __construct()
	{
		$this->value = trim($this->value);

		if( !strlen($this->value) )
		{
			$this->value = trim($this->default_value);

			if( !strlen($this->value) )
			{
				$this->is_null = true;
			}
		}

		if( $this->is_null )
		{
			if( $this->type === 'integer' ) $this->value = '0';
			else if( $this->type === 'boolean' ) $this->value = 'FALSE';
			else if( $this->type === 'json' ) $this->value = '{}';
		}
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

	public function getValue()
	{
		if( $this->type === 'integer' )
		{
			return (int) $this->value;
		}

		if( $this->type === 'boolean' )
		{
			return strtoupper($this->value) === 'TRUE';
		}

		if( $this->type === 'json' )
		{
			try {
				$get = Json::parse( $this->value, true );
				if( !is_array($get) )
				{
					$get = [];
				}
			}
			catch( \InvalidArgumentException $e ) {
				App::Log("Cannot ready json config value '{$this->name}'");
				App::Log($e);
				$get = [];
			}

			return $get;
		}

		return $this->value;
	}

	public function getTextValue()
	{
		return $this->value;
	}
}