<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 4:11
 */

namespace EApp\SecurityFilter\Traits;

use EApp\SecurityFilter\Exceptions\FilterException;
use EApp\SecurityFilter\Exceptions\RequiredException;
use EApp\SecurityFilter\Exceptions\ValidateException;
use EApp\SecurityFilter\Interfaces\ValidateFilterInterface;
use EApp\Support\Str;

trait FilterTrait
{
	protected $rules = [];

	protected $php_validation =
		[
			"int" => FILTER_VALIDATE_INT,
			"integer" => FILTER_VALIDATE_INT,
			"float" => FILTER_VALIDATE_FLOAT,
			"double" => FILTER_VALIDATE_FLOAT,
			"number" => FILTER_VALIDATE_FLOAT,
			"bool" => FILTER_VALIDATE_BOOLEAN,
			"email" => FILTER_VALIDATE_EMAIL,
			"ip" => FILTER_VALIDATE_IP,
			"ip_v4" => FILTER_FLAG_IPV4,
			"ip_v6" => FILTER_FLAG_IPV6,
			"domain" => FILTER_VALIDATE_DOMAIN,
			"mac" => FILTER_VALIDATE_MAC,
			"url" => FILTER_VALIDATE_URL
		];

	public function addRule( $name, \Closure $callback )
	{
		$this->rules[$name] = $callback;
		return $this;
	}

	public function filter( $value, array $filter, $name = null )
	{
		if( empty($filter["name"]) )
		{
			throw new FilterException( $name, "Unknown filter name" );
		}

		// default filters

		$filter_name = Str::lower($filter["name"]);

		if( $filter_name === "trim" ) return $this->filterTrim( $value, $filter, $name );
		if( $filter_name === "required" ) return $this->filterRequired( $value, $filter, $name );
		if( $filter_name === "length" ) return $this->filterLength( $value, $filter, $name );
		if( $filter_name === "regexp" ) return $this->filterRegExp( $value, $filter, $name );

		if( $filter_name === "lower" ) return Str::lower($value);
		if( $filter_name === "upper" ) return Str::upper($value);
		if( $filter_name === "title" ) return Str::title($value);

		// added

		if( isset($this->rules[$filter_name]) )
		{
			$func = $this->rules[$filter_name];
			return $func( $value, $filter, $name );
		}

		// php validation

		if( isset($this->php_validation[$filter_name]) )
		{
			$value = filter_var(
				$value,
				$this->php_validation[$filter_name],
				isset($filter["options"]) && is_array($filter["options"]) ? $filter["options"] : []
			);
			if( $filter_name !== 'bool' && $value === false )
			{
				throw new ValidateException($name, $this->getErrorMessage($filter,"Invalid data format"));
			}
			return $value;
		}

		// custom class

		if( $filter_name === 'class' )
		{
			if( !isset($filter["class_name"]) )
			{
				throw new FilterException( $name, "Unknown filter class_name" );
			}

			$class_name = $filter["class_name"];
			if( !class_exists($class_name, true))
			{
				throw new FilterException( $name, "Filter class_name '{$class_name}' not found" );
			}

			$class = new $class_name($name, $filter);
			if( $class instanceof ValidateFilterInterface )
			{
				$value = $class->filter($value);
			}
			else
			{
				throw new FilterException( $name, "Filter class_name '{$class_name}' must be inherited from the ValidateFilterInterface interface" );
			}
		}

		return $value;
	}

	protected function filterTrim( $value, $filter, $name )
	{
		if( isset($filter["dir"]) )
		{
			$dir = strtolower($filter["dir"]);
			if( $dir === "left" )
			{
				return ltrim($value);
			}
			else if( $dir === "right" )
			{
				return rtrim($value);
			}
		}
		return trim($value);
	}

	protected function filterRequired( $value, $filter, $name )
	{
		if( ! strlen($value) )
		{
			throw new RequiredException($name, $this->getErrorMessage($filter));
		}

		return $value;
	}

	protected function filterLength( $value, $filter, $name )
	{
		if( isset($filter["number"]) && $filter["number"] )
		{
			if( !strlen($value) )
			{
				$value = 0;
			}
			else if( !is_numeric($value) )
			{
				throw new ValidateException($name, "Enter the number");
			}

			$num = $filter["number"];
			if( is_bool($num) )
			{
				$value = (float) $value;
			}
			else
			{
				$num = strtolower($num);
				if( $num === 'float' || $num === 'double' )
				{
					$value = (float) $value;
				}
				else
				{
					$value = (int) $value;
				}
			}

			if( isset($filter["min"]) && $value < $filter["min"] )
			{
				throw new ValidateException($name, "The minimum value of the number is '" . $filter["min"] . "'");
			}

			if( isset($filter["max"]) && $value > $filter["max"] )
			{
				throw new ValidateException($name, "The maximum value of the number is '" . $filter["max"] . "'");
			}
		}
		else
		{
			$len = Str::len($value);

			if( isset($filter["min"]) && $len < $filter["min"] )
			{
				throw new ValidateException($name, "The minimum length is '" . $filter["min"] . "' characters");
			}

			if( isset($filter["max"]) && $value > $filter["max"] )
			{
				throw new ValidateException($name, "The maximum length is '" . $filter["max"] . "' characters");
			}

			if( isset($filter["equiv"]) && $filter["equiv"] !== $len )
			{
				throw new ValidateException($name, "Number of characters must be '" . $filter["equiv"] . "'");
			}
		}

		return $value;
	}

	protected function filterRegExp( $value, $filter, $name )
	{
		$is_use = isset($filter["format"]);

		if( $is_use )
		{
			$value = preg_replace($filter["format"], isset($filter['replacement']) ? $filter['replacement'] : '', $value );
		}

		if( isset($filter['validation']) )
		{
			$is_use = preg_match($filter['validation'], $value);
			if( !$is_use )
			{
				throw new ValidateException($name, $this->getErrorMessage($filter));
			}
		}

		if( !$is_use )
		{
			throw new FilterException($name, "Unknown filter format or validation arguments");
		}

		return $value;
	}

	protected function getErrorMessage( array $filter, $default = "" )
	{
		return isset($filter['error']) ? $filter['error'] : $default;
	}
}