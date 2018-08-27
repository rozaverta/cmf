<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.08.2018
 * Time: 13:41
 */

namespace EApp\Cache\Properties;

class Property
{
	protected $name;

	protected $value;

	public function __construct( string $name, $value )
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getOriginalName(): string
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getOriginalValue()
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return str_replace("_", " ", ucfirst($this->name));
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
}