<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 15:31
 */

namespace EApp\Cmd\IO;

class ConfigOption extends Option
{
	protected $name = null;

	protected $title = null;

	protected $type = "string";

	protected $required = false;

	protected $enum = [];

	/**
	 * @var bool | \Closure
	 */
	protected $type_of = false;

	protected $ignore_empty = false;

	public function __construct( string $name, string $answer, array $property = [])
	{
		parent::__construct( $answer, $property["default"] ?? null );
		$this->name = $name;

		if( isset($property["enum"]) && is_array($property["enum"]) )
		{
			$this->setEnum($property["enum"]);
		}
		else
		{
			if( isset($property["type"]) )
			{
				$this->setType($property["type"]);
			}
			if( $this->type !== "boolean" && isset($property["required"]) )
			{
				$this->required = (bool) $property["required"];
			}
			if( isset($property["type_of"]) && $property["type_of"] instanceof \Closure )
			{
				$this->setTypeOf($property["type_of"]);
			}
		}

		if( isset($property["title"]) )
		{
			$this->setTitle($property["title"]);
		}

		if( isset($property["ignore_empty"]) )
		{
			$this->setIgnoreEmpty((bool) $property["ignore_empty"]);
		}

		// check default type

		if( $this->type === "boolean" && ! is_bool($this->value) )
		{
			$this->value = false;
		}

		if( $this->type === "number" && ! (is_int($this->value) || is_float($this->value)) )
		{
			$this->value = is_numeric($this->value) ? (float) $this->value : 0;
		}
	}

	public function setEnum(array $variant)
	{
		$this->type = "enum";
		$this->enum = $variant;
		return $this;
	}

	/**
	 * Set new value
	 *
	 * @param $value
	 * @return $this
	 */
	public function setValue($value)
	{
		// set default
		if( $value === "" )
		{
			$value = $this->value;
		}

		if( $this->type === "boolean" )
		{
			$value = is_bool($value) ? $value : in_array($value, ['y', 'yes', 'on', '1']);
		}
		else if( $this->type === "number" )
		{
			if( ! is_numeric($value) )
			{
				throw new \InvalidArgumentException($this->getTheTitle() . " must be a number");
			}
			$value = (float) $value;
			if( $this->required && $value === 0 )
			{
				throw new \InvalidArgumentException($this->getTheTitle() . " is required");
			}
		}
		else if( $this->type === "enum" )
		{
			if( ! in_array($value, $this->enum, true) )
			{
				$variant = "'" . implode("' or '", $this->enum) . "'";
				throw new \InvalidArgumentException($this->getTheTitle() . " must be equal " . $variant);
			}
		}
		else
		{
			$value = trim($value);
			if( $this->required && ! strlen($value) )
			{
				throw new \InvalidArgumentException($this->getTheTitle() . " is required");
			}
		}

		if( $this->type_of )
		{
			$value = call_user_func($this->type_of, $value);
			if( $value === false && $this->type !== "boolean" )
			{
				throw new \InvalidArgumentException($this->getTheTitle() . " value is invalid");
			}
		}

		$this->value = $value;
		return $this;
	}

	/**
	 * Fill data
	 *
	 * @param $load
	 * @return bool
	 */
	public function fill( & $load ): bool
	{
		if( $this->isIgnoreEmpty() && ($this->value === 0 || $this->value === "") )
		{
			return false;
		}

		$load[$this->getName()] = $this->getValue();
		return true;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType( string $type )
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * @param bool $required
	 * @return $this
	 */
	public function setRequired( bool $required )
	{
		$this->required = $required;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title ?? $this->answer;
	}

	/**
	 * @param null $title
	 * @return $this
	 */
	public function setTitle( $title )
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @param \Closure $type_of
	 * @return $this
	 */
	public function setTypeOf( \Closure $type_of )
	{
		$this->type_of = $type_of;
		return $this;
	}

	protected function getTheTitle()
	{
		if( $this->title )
		{
			return "The " . $this->title;
		}
		else
		{
			return $this->answer;
		}
	}

	/**
	 * @return bool
	 */
	public function isIgnoreEmpty(): bool
	{
		return ! $this->required && $this->ignore_empty;
	}

	/**
	 * @param bool $ignore_empty
	 * @return $this
	 */
	public function setIgnoreEmpty( bool $ignore_empty )
	{
		$this->ignore_empty = $ignore_empty;
		return $this;
	}
}