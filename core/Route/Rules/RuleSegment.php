<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:01
 */

namespace EApp\Route\Rules;

use EApp\Route\Interfaces\RuleInterface;

class RuleSegment implements RuleInterface
{
	use MatchTypeTrait;

	private $name;

	private $open;

	private $type;

	private $type_properties;

	private $prefix;

	private $suffix;

	private $single = true;

	private $min = 0;

	private $max = 1;

	public function __construct( string $name, string $type, string $countable, string $prefix, string $suffix, $type_properties = null )
	{
		$this->name = $name;
		$this->type = strlen($type) ? $type : "name";
		$this->prefix = $prefix;
		$this->suffix = $suffix;
		$this->type_properties = $type_properties;

		$countable = trim($countable);
		if( !strlen($countable) || $countable === "=" )
		{
			$this->min = 1;
		}
		else if( $countable === "+" )
		{
			$this->min = 1;
			$this->max = 100;
			$this->single = false;
		}
		else if( $countable === "*" )
		{
			$this->max = 100;
			$this->single = false;
		}
		else if( is_numeric($countable) && $countable >= 1 )
		{
			$this->min = (int) $countable;
			$this->max = (int) $countable;
			$this->single = false;
		}
		else if( preg_match('/^([1-9]\d*), ?([1-9]\d*)$/', $countable, $m) )
		{
			$min = (int) $m[1];
			$max = (int) $m[2];
			$this->single = false;

			if( $min > $max )
			{
				$this->min = $max;
				$this->max = $min;
			}
			else
			{
				$this->min = $min;
				$this->max = $max;
			}
		}
		else if( $countable !== "?" )
		{
			throw new \InvalidArgumentException("Invalid countable format '{$countable}'");
		}
	}

	public static function __set_state($data)
	{
		$instance = new static($data["name"], $data["type"], "", $data["prefix"], $data["suffix"], $data["type_properties"] ?? null);
		if( $data["open"] && is_bool($data["open"])) $instance->open = $data["open"];
		if( $data["single"] && is_bool($data["single"])) $instance->single = $data["single"];
		if( $data["min"] && is_int($data["min"]) ) $instance->min = $data["min"];
		if( $data["max"] && is_int($data["max"]) ) $instance->max = $data["max"];
		return $instance;
	}

	/**
	 * @return int
	 */
	public function getMin(): int
	{
		return $this->min;
	}

	/**
	 * @return int
	 */
	public function getMax(): int
	{
		return $this->max;
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
	public function getPrefix(): string
	{
		return $this->prefix;
	}

	/**
	 * @return string
	 */
	public function getSuffix(): string
	{
		return $this->suffix;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return null
	 */
	public function getTypeProperties()
	{
		return $this->type_properties;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->min > 0;
	}

	/**
	 * @return bool
	 */
	public function isSingle(): bool
	{
		return $this->single;
	}

	/**
	 * @param string $value
	 * @param null $match
	 * @return bool
	 */
	public function match( string $value, & $match = null ): bool
	{
		$prf = strlen($this->prefix);
		$suf = strlen($this->suffix);
		$len = strlen($value);

		$valid =
			$prf + $suf + 1 > $len
			|| $prf > 0 && substr($value, 0, $prf) !== $this->prefix
			|| $suf > 0 && substr($value, $len - $suf) !== $this->suffix;

		if( ! $valid )
			return false;

		if( $suf > 0 )
			$value = substr($value, 0, $len - $suf);

		if( $prf > 0 )
			$value = substr($value, $prf);

		$type = $this->getType();
		$valid = false;

		if( $type === "name" )
		{
			$valid = ! preg_match('/[^a-zA-Z0-9_\-]/', $value);
		}
		else if( $this->matchBase($value, $type, $this->type_properties, $match) )
		{
			$valid = true;
		}

		if( ! $valid )
		{
			return false;
		}

		if( is_null($match) )
		{
			$match = $value;
		}

		return true;
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
}