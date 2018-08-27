<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.08.2018
 * Time: 13:41
 */

namespace EApp\Cache\Properties;

class PropertyMemory extends Property
{
	protected $count;

	public function __construct( $name, $value, int $count )
	{
		parent::__construct( $name, $value );
		$this->value = (int) $this->value;
		$this->count = $count;
	}

	/**
	 * @param int $value
	 * @param int $count
	 * @return $this
	 */
	public function add( int $value, int $count = 1 )
	{
		$this->count += $count;
		$this->value += $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return int
	 */
	public function getCount(): int
	{
		return $this->count;
	}
}