<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 1:50
 */

namespace EApp\Cache;

abstract class Store implements CacheStoreInterface
{
	protected $name;

	protected $driver;

	protected $life = 0;

	public function __construct(string $name, int $life = 0)
	{
		$this->name = $name;
		if( $life > 0 )
		{
			$this->life = $life;
		}
	}

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
	public function getDriver(): string
	{
		if( !isset($this->driver) )
		{
			$name = (new \ReflectionClass($this))->getShortName();
			$this->driver = strtolower(substr($name, strlen($name) - 5));
		}
		return $this->driver;
	}

	/**
	 * @return int
	 */
	public function getLife(): int
	{
		return $this->life;
	}
}