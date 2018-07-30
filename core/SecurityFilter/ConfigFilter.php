<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.12.2017
 * Time: 15:03
 */

namespace EApp\SecurityFilter;

use EApp\Component\Module;
use EApp\SecurityFilter\Exceptions\FilterException;
use EApp\SecurityFilter\Traits\FilterDataBaseTrait;
use EApp\SecurityFilter\Traits\FilterTrait;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Json;
use EApp\Support\Str;
use EApp\System\Files\FileResource;

class ConfigFilter
{
	use FilterTrait {
		filter as baseFilter;
	}

	use FilterDataBaseTrait {
		toDataBaseFormat as baseDataBaseFormat;
	}

	/**
	 * @var Module
	 */
	protected $module;

	protected $module_rules = [];

	public function __construct( Module $module )
	{
		$this->module = $module;

		try {
			$file = new FileResource("filters", null, $module);
			$file->ready();
		}
		catch(NotFoundException $e) {
			return;
		}

		if($file->getType() === "#/security_filters")
		{
			foreach($file->getOr("items", []) as $filter)
			{
				$rule = $filter["name"];
				$filter["name"] = "class";
				if(strpos($filter["class_name"], "\\") === false)
				{
					$filter["class_name"] = $module->get("name_space") . "Filters\\" . $filter["class_name"];
				}
				$this->module_rules[$rule] = $filter;
			}
		}
	}

	public function filter( $value, array $filter, $name = null )
	{
		if( isset($filter["multiply"]) && $filter["multiply"] === true )
		{
			if( !is_array($value) )
			{
				$value = strlen($value) ? (array) Json::parse($value, true) : [];
			}

			$result = [];
			foreach($value as $one_value)
			{
				$result[] = $this->filterOnce( $one_value, $filter, $name );
			}

			return $result;
		}
		else
		{
			return $this->filterOnce($value, $filter, $name);
		}
	}

	public function toDataBaseFormat( $value, array $filter, $name = null )
	{
		if( isset($filter["multiply"]) && $filter["multiply"] === true )
		{
			if( !is_array($value) )
			{
				$value = (array) $value;
			}

			$result = [];
			foreach($value as $one_value)
			{
				$result[] = $this->toDataBaseFormatOnce( $one_value, $filter, $name );
			}

			return Json::stringify($result);
		}
		else
		{
			return $this->toDataBaseFormatOnce($value, $filter, $name);
		}
	}

	protected function filterOnce( $value, array $filter, $name )
	{
		if( !empty($filter["name"]) )
		{
			$filter_name = Str::lower($filter["name"]);
			if( isset($this->module_rules[$filter_name]) )
			{
				return $this->baseFilter( $value, $this->module_rules[$filter_name], $name );
			}

			if($filter_name === "bool" || $filter_name === "boolean")
			{
				return $this->filterDataBaseBoolean($value, [], $name);
			}

			if($filter_name === "json")
			{
				return $this->filterDataBaseJson($value, [], $name);
			}

			if(in_array($filter_name, ["time", "date_time", "date", "year", "timestamp"], true))
			{
				return $this->filterTime($value, ["type" => $filter_name], $name);
			}
		}

		return $this->baseFilter( $value, $filter, $name );
	}

	protected function toDataBaseFormatOnce( $value, array $filter, $name )
	{
		if( !empty($filter["name"]) )
		{
			$filter_name = Str::lower($filter["name"]);
			if( isset($this->module_rules[$filter_name]) )
			{
				return $this->baseDataBaseFormat( $value, $this->module_rules[$filter_name], $name );
			}

			if($filter["name"] === "json")
			{
				if( $value === "" || $value === null )
				{
					$value = [];
				}
				else if(is_object($value))
				{
					$value = get_object_vars($value);
				}
				else if(!is_array($value))
				{
					throw new FilterException( $name, "Json filter type must be array or object" );
				}
			}
		}

		return $this->baseDataBaseFormat( $value, $filter, $name );
	}
}
