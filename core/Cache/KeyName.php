<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:13
 */

namespace EApp\Cache;

abstract class KeyName
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

	public function getKey(): string
	{
		return $this->keyPrefix() . $this->delimiter . $this->keyName();
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