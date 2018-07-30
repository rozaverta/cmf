<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.01.2015
 * Time: 19:57
 */

namespace EApp\CI;

use EApp\Support\Traits\SingletonInstance;

/**
 * Class Php
 * @package CI
 * @method static Php getInstance()
 */
class Php
{
	use SingletonInstance;

	const ARRAY_PRETTY_PRINT = 1;
	const SHORT_ARRAY_SYNTAX = 2;

	private $pretty = false;
	private $array_open = '[';
	private $array_close = ']';
	private $max_depth = 10;
	private $depth = [];

	/**
	 * Create data string `$data = 'value';`
	 * 
	 * @param mixed $value
	 * @param string $name
	 * @param bool $init
	 * @param bool $smart_depth
	 * @return string
	 */
	public function data( $value, $name = "data", $init = true, $smart_depth = false )
	{
		$is_object = false;
		$get = '';
		$map = '$' . $name;

		if( is_object( $value ) )
		{
			$is_object = true;
		}
		else if( ! is_array( $value ) )
		{
			$get = $init ? ( $map . ' = ' . $this->fromType( $value, 0 ) . ";" ) : "";
			$this->clean();
			return $get;
		}

		$this->depth =
			[
				0 => & $value
			];

		if( $init )
		{
			$get .= $map;
			$get .= $is_object ? " = new \\ArrayIterator();\n" : " = {$this->array_open}{$this->array_close};\n";
		}

		if( $is_object && !$this->isTraversable($value) )
		{
			$value = get_object_vars( $value );
		}

		$j = 0;
		$short = $smart_depth && count($value) < 6;

		foreach( $value as $key => $val )
		{
			$get .= $map;
			if( $key === $j )
			{
				$key = $j ++;
				$get .= "[]";
			}
			else
			{
				$key = $this->string( $key );
				$get .= '[' . $key . ']';
			}

			if( $short && is_array($val) && count($val) > 9 )
			{
				$get .= " = {$this->array_open}{$this->array_close};\n";
				$key = $map . '[' . $key . ']';
				$j2 = 0;

				foreach( $val as $key2 => $val2 )
				{
					$get .= $key;
					if( $key2 === $j2 )
					{
						++ $j2;
						$get .= "[] = ";
					}
					else
					{
						$get .= '[' . $this->string( $key2 ) . '] = ';
					}
					$get .= $this->fromType( $val2, 2, 0 ) . ";\n";
				}
			}
			else
			{
				$get .= ' = ' . $this->fromType( $val, 1, 0 ) . ";\n";
			}
		}

		$this->clean();
		return $get;
	}

	public function php( $val )
	{
		$get = $this->fromType( $val, 0 );
		$this->clean();
		return $get;
	}

	/**
	 * @param array | object $values
	 * @return string
	 */
	public function arrayList( $values )
	{
		$is_object = false;

		if( is_object($values) )
		{
			$is_object = true;
		}
		else if( !is_array($values) )
		{
			$get = $this->array_open . $this->array_close;
			$this->clean();
			return $get;
		}

		$this->depth =
			[
				0 => & $values
			];

		if( $is_object && ! $this->isTraversable($values) )
		{
			$values = get_object_vars( $values );
		}

		$values = (array) $values;
		$get = $this->array_open;
		$first = true;

		foreach( $values as $val )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$get .= ",";
			}

			$get .= $this->fromType( $val, 1 );
		}

		$get .= $this->array_close;
		$this->clean();
		return $get;
	}

	/**
	 * @param array | object $values
	 * @return string
	 */
	public function assoc( $values )
	{
		$is_object = false;

		if( is_object($values) )
		{
			$is_object = true;
		}
		else if( !is_array($values) )
		{
			$get = $this->array_open . $this->array_close;
			$this->clean();
			return $get;
		}

		if( $is_object && ! $this->isTraversable($values) )
		{
			$values = get_object_vars( $values );
		}

		$this->depth =
			[
				0 => & $values
			];

		$get = $this->array_open;
		$first = true;
		$j = 0;

		foreach( $values as $key => $value )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$get .= ", ";
			}

			if( $j === $key )
			{
				++ $j;
			}
			else
			{
				$get .= $this->string( $key ) . ' => ';
			}

			$get .= $this->fromType( $value, 1 );
		}

		$get .= $this->array_close;
		$this->clean();
		return $get;
	}

	public function string( $str )
	{
		if( is_string($str) || is_int($str) || is_double($str) )
		{
			return var_export((string) $str, true);
		}

		else if( is_object($str) && method_exists($str, "__toString") )
		{
			return var_export((string) $str, true);
		}

		else {
			$get = '""';
		}

		$this->clean();
		return $get;
	}

	public function config( $flag, $depth = 10 )
	{
		$flag = (int) $flag;
		$depth = (int) $depth;

		// display format
		if( $flag & self::SHORT_ARRAY_SYNTAX )
		{
			$this->array_open = '[';
			$this->array_close = ']';
		}
		else
		{
			$this->array_open = 'array(';
			$this->array_close = ')';
		}

		// pretty print
		if( $flag & self::ARRAY_PRETTY_PRINT )
		{
			$this->pretty = true;
		}
		else
		{
			$this->pretty = false;
		}

		// max depth level
		if( $depth < 1 )
		{
			$this->max_depth = 0;
		}
		else if( $depth > 256 )
		{
			$this->max_depth = 256;
		}
		else {
			$this->max_depth = $depth;
		}

		return $this;
	}

	// private

	private function getAssoc( $values, $level, $printLevel )
	{
		$tab = $this->pretty ? str_repeat("\t", $printLevel) : "";
		$out = $this->array_open;
		$first = true;

		$is_array = is_array($values);
		if($is_array)
		{
			$i = 0;
			foreach(array_keys($values) as $key)
			{
				if( $key !== $i ++ )
				{
					$is_array = false;
					break;
				}
			}
		}

		foreach( $values as $key => $value )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$out .= ", ";
			}

			if( $this->pretty )
			{
				$out .= "\n\t" . $tab;
			}

			if( ! $is_array )
			{
				$out .= (is_int($key) ? $key : $this->string( $key )) . ' => ';
			}

			$out .= $this->fromType( $value, $level + 1, $printLevel + 1 );
		}

		if( $this->pretty && !$first )
		{
			$out .= "\n" . $tab;
		}

		return $out . $this->array_close;
	}

	private function isTraversable( $object )
	{
		if( PHP_VERSION >= 7.1 )
		{
			return is_iterable($object);
		}
		else
		{
			return $object instanceof \Traversable;
		}
	}

	private function isDepth( & $val, $level )
	{
		if( $level < 1 )
		{
			return false;
		}

		for( $i = 0; $i < $level; $i++ )
		{
			if( $this->depth[$i] === $val )
			{
				return true;
			}
		}

		return false;
	}

	private function clean()
	{
		$this->pretty = false;
		$this->array_open  = '[';
		$this->array_close  = ']';
		$this->max_depth = 10;
		$this->depth = [];
	}

	private function fromType( & $val, $level = 0, $printLevel = 0 )
	{
		$type = gettype( $val );
		$is_object = $type == "object";

		if( $is_object )
		{
			// fix recursive
			if( $this->isDepth($val, $level) )
			{
				return $this->array_open . $this->array_close;
			}
			else
			{
				$this->depth[$level] = & $val;
			}

			$type = "array";
			if( !$this->isTraversable($val) )
			{
				$val = get_object_vars( $val );
			}
		}

		switch( $type )
		{
			case "array" :

				if( !$is_object )
				{
					if( $this->isDepth($val, $level) )
					{
						return $this->array_open . $this->array_close;
					}
					else
					{
						$this->depth[$level] = & $val;
					}
				}

				return $level >= $this->max_depth ? ($this->array_open . $this->array_close) : $this->getAssoc( $val, $level, $printLevel );

			case "boolean"  : return $val ? "true" : "false";
			case "resource" :
			case "NULL"     : return "NULL";
			case "double"   :
			case "integer"  : return $val;
		}

		return $this->string( $val );
	}
}