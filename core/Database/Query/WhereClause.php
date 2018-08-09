<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2018
 * Time: 14:33
 */

namespace EApp\Database\Query;

use EApp\Support\Interfaces\InstanceStateConstructable;

class WhereClause implements InstanceStateConstructable
{
	protected $name;

	protected $operator = "=";

	protected $value;

	protected $not = false;

	private static $invert = [
		"="  => "!=",
		"!=" => "=",
		">"  => "<=",
		">=" => "<",
		"<"  => ">=",
		"<=" => ">",
		"<<" => ">>",
		">>" => "<<"
	];

	/**
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	private static $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
		'&', '|', '^', '<<', '>>',
		'like', 'regexp', 'in', 'null'
	];

	public function __construct( string $name, $value = null, $operator = null, bool $not = false )
	{
		$this->name = $name;
		$this->value = $value;

		if( ! is_null($operator) )
		{
			$this->setOperator($operator);
		}
		else if( is_null($value) )
		{
			$this->operator = "null";
		}
		else if( is_array($value) )
		{
			$this->operator = "in";
		}

		if( $not )
		{
			$this->not = true;
		}
	}

	public static function createInstance( ... $args )
	{
		return new self(... $args);
	}

	public function getInstanceState(): array
	{
		return [
			$this->name, $this->value, $this->operator, $this->not
		];
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
	public function getOperator(): string
	{
		return $this->operator;
	}

	/**
	 * @param string $operator
	 * @return $this
	 */
	public function setOperator( string $operator )
	{
		$operator = strtolower(trim($operator));
		if( in_array($operator, self::$operators, true) )
		{
			$this->operator = $operator;
		}
		return $this;
	}

	public function isIn()
	{
		return $this->operator === "in";
	}

	public function setIn( bool $set = true )
	{
		return $this->setOperatorThen($set, "in");
	}

	public function isNull()
	{
		return $this->operator === "null";
	}

	public function setNull( bool $set = true )
	{
		return $this->setOperatorThen($set, "null");
	}

	public function isLike()
	{
		return $this->operator === "like";
	}

	public function setLike( bool $set = true )
	{
		return $this->setOperatorThen($set, "like");
	}

	public function isRegexp()
	{
		return $this->operator === "like";
	}

	public function setRegexp( bool $set = true )
	{
		return $this->setOperatorThen($set, "regexp");
	}

	public function isNot()
	{
		return $this->not;
	}

	public function setNot( bool $not = true )
	{
		$this->not = $not;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		if( $this->isIn() )
		{
			return is_array($this->value) ? $this->value : [$this->value];
		}
		else
		{
			return $this->value;
		}
	}

	public function forBuilder( Builder $builder )
	{
		if( $this->isIn() )
		{
			$value = is_array($this->value) ? $this->value : [$this->value];

			if( !count($value) )    $builder->whereRaw("1=" . ($this->not ? "1" : "0"));
			else if($this->not)     $builder->whereNotIn($this->name, $value);
			else                    $builder->whereIn($this->name, $value);
		}
		else if( $this->isNull() )
		{
			if( $this->not )        $builder->whereNotNull($this->name);
			else                    $builder->whereNull($this->name);
		}
		else
		{
			$operator = $this->operator;
			if( $this->not ) {
				if( $this->isLike() || $this->isRegexp() ) $operator = "not " . $operator;
				else if( isset(self::$invert[$operator]) ) $operator = self::$invert[$operator];
			}

			$builder->where($this->name, $operator, $this->value);
		}

		return $this;
	}

	/**
	 * @param bool $set
	 * @param string $operator
	 * @return $this
	 */
	private function setOperatorThen( bool $set, string $operator )
	{
		if( $set )
		{
			$this->operator = $operator;
		}
		else if( $this->operator === $operator )
		{
			$this->operator = "=";
		}
		return $this;
	}
}