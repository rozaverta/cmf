<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 17:05
 */

namespace EApp\Support\Traits;

use ArrayAccess;

/**
 * @property array $items
 */
trait Compare
{
	public function equiv( $name, $value, $strict = true )
	{
		if( ! array_key_exists($name, $this->items) )
		{
			return false;
		}

		if( $strict )
		{
			return $this->items[$name] === $value;
		}
		else
		{
			return $this->items[$name] == $value;
		}
	}

	public function inItems( $value, $strict = null )
	{
		return in_array( $value, $this->items, $strict );
	}

	public function inArray( $name, $assoc, $strict = false )
	{
		if( ! array_key_exists($name, $this->items) )
		{
			return false;
		}
		else
		{
			return in_array( $this->items[$name], (array) $assoc, $strict );
		}
	}

	public function inZero( $name, $operator = "=", $strict = false )
	{
		if( ! isset( $this->items[$name] ) ||
			$strict && ! is_int( $this->items[$name] ) ||
			! $strict && ! is_numeric( $this->items[$name] ) ) {
			return false;
		}

		$value = (int) $this->items[$name];

		switch( $operator ) {
			case "="  :
			case "==" : return $value === 0;
			case "<"  : return $value < 0;
			case "<=" : return $value <= 0;
			case ">"  : return $value > 0;
			case ">=" : return $value >= 0;
			case "!=" :
			case "!"  : return $value !== 0;
		}

		return false;
	}

	public function interval( $name, $min, $max, $strict = false )
	{
		if( ! isset( $this->items[$name] ) ||
			$strict && ! is_int( $this->items[$name] ) ||
			! $strict && ! is_numeric( $this->items[$name] ) ) {
			return false;
		}

		$value = (int) $this->items[$name];

		return $value >= $min && $value <= $max;
	}

	public function isArray( $name )
	{
		return isset( $this->items[$name] ) && (is_array( $this->items[$name] ) || $this->items[$name] instanceof ArrayAccess);
	}

	public function isInt( $name )
	{
		return isset( $this->items[$name] ) && is_int( $this->items[$name] );
	}

	public function isNumeric( $name )
	{
		return isset( $this->items[$name] ) && is_numeric( $this->items[$name] );
	}

	public function isFill( $name )
	{
		if( !isset( $this->items[$name] ) )
		{
			return false;
		}
		if( is_string($this->items[$name]) )
		{
			return strlen(trim($this->items[$name])) > 0;
		}
		else
		{
			return ! empty($this->items[$name]);
		}
	}
}
