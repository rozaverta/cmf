<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace EApp\Event;

use EApp\Event\Interfaces\EventInterface;

class Event implements EventInterface
{
	private $name;
	private $params = [];

	public function __construct($name, $params = null)
	{
		$this->name = $name;
		if( is_array($params) )
		{
			$this->params = $params;
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function getParam( $name )
	{
		return isset( $this->params[$name] ) ? $this->params[$name] : null;
	}
}