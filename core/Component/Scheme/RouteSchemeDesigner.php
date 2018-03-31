<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 2:51
 */

namespace EApp\Component\Scheme;

use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Json;

class RouteSchemeDesigner implements Arrayable
{
	/**
	 * ModuleInstance unique identifier in the database table.
	 *
	 * @var int
	 */
	public $id;

	public $path;

	/**
	 * @var array | object | string
	 */
	public $properties;

	public $type = "path";

	public function __construct()
	{
		$this->id = (int) $this->id;

		if( is_object($this->properties) )
		{
			$this->properties = get_object_vars($this->properties);
		}
		else if( ! is_array($this->properties) )
		{
			$prop = $this->properties;
			if( strlen($prop) && $prop[0] === '{' )
			{
				$this->properties = Json::parse($prop, true);
			}
			else
			{
				$this->properties = [];
			}
		}

		if( $this->path === "@index" || $this->path === "@all" || $this->path === "@404" )
		{
			$this->type = substr($this->path, 1);
		}
		else if( preg_match('/^@(math|uri|query|of|host):(.+?)$/', $this->path, $m) )
		{
			$type = $m[1];
			$math = $m[2];

			$this->type = $type;

			if( $type == "query" )
			{
				$math = [];
				parse_str( $m[2], $math );
			}

			$this->{$type} = $math;
		}
		else
		{
			$this->path = trim( $this->path, "/" );
		}
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars($this);
	}
}