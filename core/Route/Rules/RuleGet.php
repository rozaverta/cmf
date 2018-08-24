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

class RuleGet implements RuleInterface
{
	use MatchTypeTrait;

	private $query_name;

	private $name;

	private $required;

	private $type;

	private $type_properties;

	public function __construct( string $query_name, string $name, bool $required = true, string $type = "", $type_properties = null )
	{
		$this->query_name = $query_name;
		$this->name = $name;
		$this->required = $required;
		$this->type = strlen($type) ? Str::lower($type) : "all";
		$this->type_properties = $type;
	}

	public static function __set_state($data)
	{
		return new static(
			$data["query_name"],
			$data["name"],
			(bool) $data["required"] ?? true,
			$data["type"] ?? "",
			$data["type_properties"] ?? null
		);
	}

	/**
	 * @return string
	 */
	public function getQueryName(): string
	{
		return $this->query_name;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getTypeProperties()
	{
		return $this->type_properties;
	}

	public function match( string $value, & $match = null ): bool
	{
		$type = $this->getType();
		switch($type)
		{
			case "logic":
				if( in_array($value, ["", "0", "1"]) )
				{
					$match = $value === "1";
					return true;
				}
				break;

			case "empty":
				return strlen($value) < 1;
				break;

			case "not_empty":
				return strlen($value) > 0;
				break;
		}

		return $this->matchBase($value, $type, $this->type_properties, $match);
	}
}