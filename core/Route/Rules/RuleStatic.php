<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:01
 */

namespace EApp\Route\Rules;

use EApp\Route\Interfaces\RuleInterface;
use EApp\Support\Str;

class RuleStatic implements RuleInterface
{
	private $open = false;

	private $segment;

	private $lower = false;

	public function __construct( string $segment )
	{
		$this->segment = $segment;
	}

	public static function __set_state($data)
	{
		$instance = new static($data["segment"]);
		if( $data["open"] && is_bool($data["open"])) $instance->open = $data["open"];
		if( $data["lower"] && is_bool($data["lower"])) $instance->lower = $data["lower"];
		return $instance;
	}

	/**
	 * @param string $value
	 * @param null $match
	 * @return bool
	 */
	public function match( string $value, & $match = null ): bool
	{
		if( $this->isLower() )
		{
			$value = Str::lower($value);
		}

		return $value === $this->segment;
	}

	/**
	 * @return bool
	 */
	public function isOpen(): bool
	{
		return $this->open;
	}

	/**
	 * @param bool $open
	 * @return $this
	 */
	public function setOpen( bool $open = true )
	{
		$this->open = $open;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSegment(): string
	{
		return $this->segment;
	}

	/**
	 * @return bool
	 */
	public function isLower(): bool
	{
		return $this->lower;
	}

	/**
	 * @param bool $lower
	 * @return $this
	 */
	public function setLower( bool $lower = true )
	{
		$this->lower = $lower;
		return $this;
	}
}