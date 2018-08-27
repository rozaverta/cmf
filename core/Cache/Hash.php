<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:13
 */

namespace EApp\Cache;

abstract class Hash
{
	protected $name;

	protected $prefix;

	protected $data;

	protected $delimiter = "";

	public function __construct( string $name, string $prefix = "", array $data = [])
	{
		$this->name = $name;
		$this->prefix = $prefix;
		$this->data = $data;
	}

	public function getHash(): string
	{
		$prefix = $this->keyPrefix();
		if(strlen($prefix))
		{
			return $prefix . $this->delimiter . $this->keyName();
		}
		else
		{
			return $this->keyName();
		}
	}

	abstract public function keyName(): string;

	abstract public function keyPrefix(): string;

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getPrefix(): string
	{
		return $this->prefix;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}