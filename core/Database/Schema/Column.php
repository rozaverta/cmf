<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\Database\Schema;
use EApp\Prop;

/**
 * Class SchemaManager
 *
 * @package EApp\DB
 */
class Column
{
	use ExtraTrait;

	protected $name;

	protected $type = "string";

	protected $subtype = "";

	protected $default = null;

	protected $comment = "";

	protected $title = "";

	protected $not_null = true;

	protected $index = false;

	protected $unique = false;

	protected $primary = false;

	protected $auto_increment = false;

	protected $unsigned = false;

	protected $zerofill = false;

	protected $fixed = false;

	protected $length = 0;

	protected $precision = 0;

	protected $scale = 0;

	public function __construct( string $name, array $properties, Prop $extra = null )
	{
		$this->name  = $name;
		$this->extra = is_null($extra) ? new Prop() : $extra;

		if( isset($properties["type"]) ) $this->type = $properties["type"];

		$this->subtype = $properties["subtype"] ?? $this->type;
		$this->title = $properties["title"] ?? "Table {$name}";

		if( isset($properties["not_null"]) ) $this->not_null = (bool) $properties["not_null"];
		if( isset($properties["index"]) ) $this->index = (bool) $properties["index"];
		if( isset($properties["unique"]) ) $this->unique = (bool) $properties["unique"];
		if( isset($properties["primary"]) ) $this->primary = (bool) $properties["primary"];
		if( isset($properties["auto_increment"]) ) $this->auto_increment = (bool) $properties["auto_increment"];
		if( isset($properties["unsigned"]) ) $this->unsigned = (bool) $properties["unsigned"];
		if( isset($properties["zerofill"]) ) $this->zerofill = (bool) $properties["zerofill"];
		if( isset($properties["fixed"]) ) $this->fixed = (bool) $properties["fixed"];
		if( isset($properties["length"]) ) $this->length = (int) $properties["length"];
		if( isset($properties["precision"]) ) $this->precision = (int) $properties["precision"];
		if( isset($properties["scale"]) ) $this->scale = (int) $properties["scale"];
		if( isset($properties["default"]) ) $this->default = $properties["default"];
		if( isset($properties["comment"]) ) $this->comment = $properties["comment"];
	}

	public static function __set_state(array $data)
	{
		if( !isset($data["name"]) )
		{
			throw new \InvalidArgumentException(__CLASS__ . "::" . __METHOD__ . " 'name' property is not used");
		}

		return new static(
			$data["name"],
			$data,
			$data["extra"] ?? null
		);
	}

	/**
	 * Update column value
	 *
	 * @param string $name
	 * @param $value
	 * @return $this
	 */
	public function set( string $name, $value )
	{
		if( $name === "default" )
		{
			if( is_scalar($value) )
				$this->default = $value;
		}
		else if( $name !== "name" && isset($this->{$name}) && gettype($this->{$name}) === gettype($value) )
		{
			$this->{$name} = $value;
		}
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
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getSubtype(): string
	{
		return $this->subtype;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return bool
	 */
	public function isNotNull(): bool
	{
		return $this->not_null;
	}

	/**
	 * @return bool
	 */
	public function isIndex(): bool
	{
		return $this->index;
	}

	/**
	 * @return bool
	 */
	public function isUnique(): bool
	{
		return $this->unique;
	}

	/**
	 * @return bool
	 */
	public function isPrimary(): bool
	{
		return $this->primary;
	}

	/**
	 * @return bool
	 */
	public function isAutoIncrement(): bool
	{
		return $this->auto_increment;
	}

	/**
	 * @return bool
	 */
	public function isUnsigned(): bool
	{
		return $this->unsigned;
	}

	/**
	 * @return bool
	 */
	public function isZerofill(): bool
	{
		return $this->zerofill;
	}

	/**
	 * @return bool
	 */
	public function isFixed(): bool
	{
		return $this->fixed;
	}

	/**
	 * @return int
	 */
	public function getLength(): int
	{
		return $this->length;
	}

	/**
	 * @return int
	 */
	public function getPrecision(): int
	{
		return $this->precision;
	}

	/**
	 * @return int
	 */
	public function getScale(): int
	{
		return $this->scale;
	}

	/**
	 * @return string
	 */
	public function getComment(): string
	{
		return $this->comment;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return ! is_null($this->default);
	}

	/**
	 * @return null
	 */
	public function getDefault()
	{
		return $this->default;
	}
}