<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 4:11
 */

namespace EApp\SecurityFilter\Traits;

use EApp\DB\Schema\Table;
use EApp\SecurityFilter\Exceptions\FilterException;
use EApp\SecurityFilter\Exceptions\ValidateException;
use EApp\SecurityFilter\Interfaces\ValidateFilterInterface;
use EApp\Support\Json;
use EApp\Support\Str;

trait FilterDataBaseTrait
{
	protected $db_date_time = [
		"time" => "H:i:s",
		"date_time" => "Y-m-d H:i:s",
		"date" => "Y-m-d",
		"year" => "Y"
	];

	public function toDataBaseFormat( $value, array $filter, $name = null )
	{
		$filter_name = isset($filter["name"]) ? Str::lower($filter["name"]) : null;

		if($value instanceof \DateTime)
		{
			switch($filter_name)
			{
				case "int":
				case "integer":
				case "float":
				case "double":
				case "number":
				case "timestamp":
					return $value->getTimestamp();
			}

			if( isset($this->db_date_time[$filter_name]) )
			{
				return $value->format($this->db_date_time[$filter_name]);
			}
		}

		if($filter_name === "bool" || $filter_name === "boolean")
		{
			return is_bool($value) ? $value : ($value ? true : false);
		}

		if($filter_name === "json")
		{
			return Json::stringify($value);
		}

		if(in_array($filter_name, ["time", "date_time", "date", "year", "timestamp"], true))
		{
			return $this->toDataBaseFormat(
				$this->filterTime($value, ["type" => $filter_name], null), $filter
			);
		}

		switch($filter_name)
		{
			case "int":
			case "integer":
				return (int) $value;

			case "float":
			case "double":
			case "number":
				return (float) $value;
		}

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
				return $class->toDataBaseFormat($value);
			}
			else
			{
				throw new FilterException( $name, "Filter class_name '{$class_name}' must be inherited from the ValidateFilterInterface interface" );
			}
		}

		return $value;
	}

	protected function filterDataBaseBoolean( $value, $filter, $name )
	{
		if( is_bool($value) )
		{
			return $value;
		}

		if( is_numeric($value) )
		{
			return $value > 0;
		}

		if( $name && $value === $name || in_array(strtolower((string) $value), ['yes', 'on', 'true'], true) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	protected function filterDataBaseJson( $value, $filter, $name )
	{
		try {
			$value = Table::toJsonArray($value);
		}
		catch( \InvalidArgumentException $e ) {
			throw new ValidateException("Invalid json data");
		}

		return $value;
	}

	protected function filterTime( $value, $filter, $name )
	{
		if($value instanceof \DateTime)
		{
			return $value;
		}

		if(is_numeric($value))
		{
			if($filter["type"] === "year")
			{
				if( is_int($value) )
				{
					$value = (string) $value;
				}

				$len = strlen($value);
				if($len < 4)
				{
					$value = substr( date("Y"), 0, 4 - $len ) . $value;
					$len = 4;
				}

				if($len === 4)
				{
					return new \DateTime($value . "-01-01 00:00:00");
				}
			}

			return new \DateTime(date("Y-m-d H:i:s", (int) $value));
		}

		return new \DateTime( (string) $value );
	}
}