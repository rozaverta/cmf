<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 2:51
 */

namespace EApp\Schemes;

use EApp\Support\Json;

class ModuleRouterSchemeDesigner extends _ModuleSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	public $type = "path";

	public $rule = "";

	/**
	 * @var array | object | string
	 */
	public $properties = [];

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

		$this->type = strtolower($this->type);

		if( $this->type === "path" )
		{
			$this->rule = trim( $this->rule, "/" );
		}
		else if( $this->type === "query" )
		{
			$rule = $this->rule;
			$this->rule = [];
			parse_str( $rule, $this->rule );
		}
	}
}