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