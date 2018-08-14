<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace EApp\Event;

use EApp\Event\Interfaces\EventInterface;

abstract class Event implements EventInterface
{
	/**
	 * Event name
	 *
	 * @var string
	 */
	protected $name = "";

	/**
	 * Events parameters
	 *
	 * @var array
	 */
	protected $params = [];

	/**
	 * Event parameters that can be changed
	 *
	 * @var array
	 */
	protected $params_allowed = [];

	/**
	 * Valid parameter types
	 *
	 * @var array
	 */
	protected $params_allowed_type = [];

	private $prevent = false;

	public function __construct(string $name, array $params = [])
	{
		$this->name = $name;
		$this->params = $params;
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Prevents the event from being passed to further listeners
	 *
	 * @return $this
	 */
	public function stopPropagation()
	{
		$this->prevent = true;
		return $this;
	}

	/**
	 * Checks if stopPropagation has been called
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool
	{
		return $this->prevent;
	}

	/**
	 * Get all events parameters
	 *
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * Get event parameter by name
	 *
	 * @param string $name parameter name
	 * @return mixed
	 */
	public function getParam( string $name )
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

		if( isset($this->params_allowed_type[$name]) && ! $this->allowType($value, $this->params_allowed_type[$name]) )
		{
			throw new \InvalidArgumentException("Invalid type for this event parameter");
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

	/**
	 * Magic set param
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->setParam($name, $value);
	}

	/**
	 * @param string $name
	 * @param mixed $type
	 * @return $this
	 */
	protected function setAllowed( string $name, $type = null )
	{
		$this->params_allowed[] = $name;
		if( ! is_null($type) ) $this->params_allowed_type[$name] = $type;
		return $this;
	}

	/**
	 * @param $value
	 * @param $type
	 * @return bool
	 */
	protected function allowType( $value, $type )
	{
		if( $type instanceof \Closure )
		{
			return (bool) $type($value);
		}

		if( is_array($type) )
		{
			foreach( $type as $item )
				if( $this->allowType($value, (string) $item) )
					return true;

			return false;
		}

		switch($type)
		{
			case "bool":
			case "boolean":
				return is_bool($value);

			case "int":
			case "integer":
				return is_int($value);

			case "float":
			case "double":
				return is_float($value);

			case "number":
				return is_numeric($value);

			case "null":
				return is_null($value);

			case "string":
			case "text":
				return is_string($value);
		}

		return class_exists($type, true) ? (new \ReflectionClass($type))->isInstance($value) : false;
	}
}