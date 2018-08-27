<?php
/**
 * Created by IntelliJ IDEA.
 * User: gosha
 * Date: 16.02.2017
 * Time: 20:26
 */

namespace EApp;

use ArrayAccess;
use ArrayIterator;
use Countable;
use EApp\Support\Str;
use IteratorAggregate;
use Traversable;
use EApp\Interfaces\Arrayable;
use EApp\Traits\GetTrait;
use EApp\Traits\SetTrait;
use EApp\Traits\CompareTrait;

class Prop implements ArrayAccess, Countable, Arrayable, IteratorAggregate
{
	use GetTrait;
	use SetTrait;
	use CompareTrait;

	protected $items = [];

	public function __construct( $prop = [], $indexKey = true )
	{
		if( is_string($prop) )
		{
			$prop = self::file($prop);
		}
		else if( $prop instanceof Arrayable )
		{
			$prop = $prop->toArray();
		}
		else if($prop instanceof Traversable)
		{
			$prop = iterator_to_array($prop);
		}

		if( is_array($prop) )
		{
			if( $indexKey )
			{
				foreach( $prop as $key => $value )
				{
					if( is_int($key) && is_string($value) )
					{
						$this->items[$value] = true;
					}
					else
					{
						$this->items[$key] = $value;
					}
				}
			}
			else
			{
				$this->items = $prop;
			}
		}
	}

	/**
	 * @param array $an_array
	 * @return static
	 */
	public static function __set_state($an_array)
	{
		return new static($an_array["items"] ?? []);
	}

	/**
	 * Get array data from file
	 *
	 * @param $name
	 * @param bool $exists
	 * @return array
	 */
	public static function file( $name, & $exists = false )
	{
		if( !defined("APP_DIR") )
		{
			return [];
		}

		$file = APP_DIR . "config" . DIRECTORY_SEPARATOR . $name . '.php';
		if( file_exists( $file ) )
		{
			$data = Helper::includeImport($file);
			$exists = true;
		}

		if( ! isset( $data ) || ! is_array( $data ) )
		{
			$data = [];
		}

		return $data;
	}

	/**
	 * Get cache property from file
	 *
	 * @param $name
	 * @return Prop
	 */
	public static function cache( $name )
	{
		static $cache = [];

		$name = (string) $name;

		if( ! isset($cache[$name]) )
		{
			$cache[$name] = new self($name);
		}

		return $cache[$name];
	}

	public function modify($name, ... $args)
	{
		if( !$this->getIs($name) )
		{
			return $this->itemsGetUndefined;
		}

		$value = $this->get($name);
		$len = count($args);
		if( $len === 1 && is_array($args[0]) )
		{
			$args = $args[0];
		}

		foreach($args as $name)
		{
			$value = $this->mdf($name, $value);
		}

		return $value;
	}

	protected function mdf($name, $value)
	{
		$name = strtolower($name);

		if( $name === 'length' )
		{
			if( is_array($value) )
			{
				return count($value);
			}
			else
			{
				return Str::len($value);
			}
		}

		if( $name === 'type' )
		{
			return gettype( $value );
		}

		if( ! is_scalar($value) )
		{
			return $value;
		}

		$value = (string) $value;

		switch( $name )
		{
			case 'trim':   return trim( $value );
			case 'ltrim':  return ltrim( $value );
			case 'rtrim':  return rtrim( $value );
			case 'escape': return htmlspecialchars( $value, ENT_COMPAT, BASE_ENCODING );
			case 'entity': return htmlentities( $value, null, BASE_ENCODING );
			case 'strip':  return strip_tags( $value );
			case 'lower':  return Str::lower( $value );
			case 'upper':  return Str::upper( $value );
			case 'title':  return Str::title( $value );
		}

		if( preg_match( '/^cut:(\d+)(?::(\d+))?/', $name, $m ) )
		{
			if( $m[2] )
			{
				return Str::cut( $value, (int) $m[1], (int) $m[2] );
			}
			else
			{
				return Str::cut( $value, 0, (int) $m[1] );
			}
		}

		return $value;
	}

	/**
	 * Get new property group
	 *
	 * @param $name
	 * @return Prop
	 */
	public function group( $name )
	{
		$name = rtrim($name, '.');
		$pref = $name . '.';
		$len = strlen($pref);
		$data = [];

		foreach( array_keys($this->items) as $key )
		{
			if( $key === $name )
			{
				$data['.'] = $this->items[$key];
			}
			else if( strlen($key) > $len && substr($key, 0, $len) === $pref )
			{
				$data[substr($key, $len)] = $this->items[$key];
			}
		}

		return new self($data);
	}

	/**
	 * Create empty array for path.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function pathMake( $path )
	{
		return $this->pathSet($path, []);
	}

	/**
	 * Set value for path.
	 *
	 * @example $this->pathSet( 'a.b.c', $value ) => $this->items['a']['b']['c'] = $value;
	 *
	 * @param string $path
	 * @param mixed $value
	 * @return $this
	 */
	public function pathSet( $path, $value )
	{
		$path = $this->createPath($path);
		if( $path[0] == 1 )
		{
			$this->offsetSet($path[1], $value);
		}
		else
		{
			$array = & $this->items;
			for( $i = 1, $len = $path[0]; $i <= $len; $i++ )
			{
				$key = $path[$i];
				if( $i == $len )
				{
					$array[$key] = $value;
				}
				else
				{
					if( !isset($array[$key]) || !is_array($array[$key]) )
					{
						$array[$key] = [];
					}

					$array = & $array[$key];
				}
			}
		}
		return $this;
	}

	/**
	 * Check value exists for path exist.
	 *
	 * @param string $path
	 * @param bool $accessible
	 * @return bool
	 */
	public function pathGetIs( $path, $accessible = false )
	{
		$path = $this->createPath($path);
		$array = & $this->items;
		$key = $path[1];

		if( $path[0] > 1 )
		{
			for( $i = 1, $len = $path[0]; $i <= $len; $i++ )
			{
				$key = $path[$i];
				if( ! array_key_exists($key, $array) )
				{
					return false;
				}
				if( $i < $len )
				{
					if( is_array($array[$key]) )
					{
						$array = & $array[$key];
					}
					else
					{
						return false;
					}
				}
			}
		}
		else if( ! array_key_exists($key, $array) )
		{
			return false;
		}

		if( $accessible )
		{
			return is_array($array[$key]);
		}

		return true;
	}

	/**
	 * Get value from path.
	 *
	 * @param string $path
	 * @return mixed
	 */
	public function pathGet( $path )
	{
		return $this->pathGetOr( $path, $this->itemsGetUndefined );
	}

	/**
	 * Get value from path or get default value if not exists.
	 *
	 * @param string $path
	 * @param mixed $default
	 * @return mixed
	 */
	public function pathGetOr( $path, $default )
	{
		$path = $this->createPath($path);
		if( $path[0] == 1 )
		{
			return $this->get($path[1]);
		}

		$array = & $this->items;
		for( $i = 1, $len = $path[0]; $i <= $len; $i++ )
		{
			$key = $path[$i];
			if( ! array_key_exists($key, $array) )
			{
				break;
			}

			if( $i == $len )
			{
				return $array[$key];
			}

			if( !isset($array[$key]) || !is_array($array[$key]) )
			{
				break;
			}

			$array = & $array[$key];
		}

		return $default;
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 *
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->items);
	}

	protected function createPath( $path )
	{
		$path = str_replace(['/', '\\', '->'], '.', $path);
		if( strpos($path, ".") === false )
		{
			return [1, $path];
		}
		else
		{
			$get = explode(".", "." . $path);
			$get[0] = count($get) - 1;
			return $get;
		}
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}
}