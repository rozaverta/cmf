<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:33
 */

namespace EApp\Event;

trait EventParamTrait
{
	protected $params = [];

	protected $params_allowed = [];

	/**
	 * Get all events parameters
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Get event parameter by name
	 *
	 * @param string $name parameter name
	 * @return mixed
	 */
	public function getParam( $name )
	{
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}

	public function setParam( $name, $value, $set_allow = true )
	{
		$allow = in_array($name, $this->params_allowed);

		if( !array_key_exists($name, $this->params) )
		{
			if(! $allow && $set_allow)
			{
				$this->params_allowed[] = $name;
			}
		}
		else if( !$allow )
		{
			throw new \Exception("You cannot change this event parameter, there is no allow");
		}

		$this->params[$name] = $value;

		return $this;
	}

	/**
	 * Magic get param
	 *
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->getParam($name);
	}
}