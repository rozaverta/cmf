<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 2:51
 */

namespace EApp\Component\Scheme;

use EApp\Database\Schema\SchemeDesigner;
use EApp\Support\Json;

class RouteSchemeDesigner extends SchemeDesigner
{
	/**
	 * ModuleConfig unique identifier in the database table.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $module_id;

	public $path;

	/**
	 * @var array | object | string
	 */
	public $properties;

	public $type = "path";

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;

		if( is_object($this->properties) )
		{
			$this->properties = get_object_vars($this->properties);
		}
		else if( ! is_array($this->properties) )
		{
			if( is_string($this->properties) && strlen($this->properties) )
			{
				$this->properties = Json::getArrayProperties($this->properties, true);
			}
			else if( ! is_array($this->properties) )
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
}