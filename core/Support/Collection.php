<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:36
 */

namespace EApp\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use EApp\Traits\CompareTrait;
use EApp\Traits\GetTrait;
use EApp\Traits\SetTrait;
use EApp\Interfaces\Arrayable;
use EApp\Interfaces\Jsonable;

class Collection implements ArrayAccess, IteratorAggregate, Arrayable, Jsonable, Countable, JsonSerializable
{
	use GetTrait;
	use SetTrait;
	use CompareTrait;

	const EACH_USE_BOTH = 1;
	const EACH_USE_KEY = 2;

	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param  mixed  $items
	 */
	public function __construct($items = [])
	{
		$this->reload($items);
	}

	/**
	 * Reload data
	 *
	 * @param array $items
	 */
	public function reload($items = [])
	{
		$this->items = $this->getItems($items);
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  callable|null  $callback
	 * @return self
	 */
	public function filter(callable $callback = null)
	{
		if ($callback)
		{
			return new self(
				array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)
			);
		}
		else
		{
			return new self(array_filter($this->items));
		}
	}

	/**
	 * @param callable $callback
	 * @param int $flag
	 * @return $this
	 */
	public function each(callable $callback, $flag = 0)
	{
		if($flag & self::EACH_USE_BOTH)
		{
			foreach($this->items as $key => $item)
			{
				call_user_func( $callback, $item, $key );
			}
		}
		else if($flag & self::EACH_USE_KEY)
		{
			foreach(array_keys($this->items) as $key)
			{
				call_user_func( $callback, $key );
			}
		}
		else
		{
			foreach($this->items as $item)
			{
				call_user_func( $callback, $item );
			}
		}
		return $this;
	}

	/**
	 * Returns all of the attributes in the collection
	 *
	 * If an optional mask array is passed, this only
	 * returns the keys that match the mask
	 *
	 * @param array $mask               The parameter mask array
	 * @param boolean $fill_with_nulls  Whether or not to fill the returned array with
	 *  values to match the given mask, even if they don't exist in the collection
	 * @return self
	 */
	public function mask(array $mask, $fill_with_nulls = false)
	{
		/*
		 * Make sure that each key in the mask has at least a
		 * null value, since the user will expect the key to exist
		 */
		if($fill_with_nulls)
		{
			$attributes = array_fill_keys($mask, null);
		}
		else
		{
			$attributes = [];
			foreach( array_keys($mask) as $key )
			{
				if( ! is_int($key) )
				{
					$attributes[$key] = $mask[$key];
					unset($mask[$key]);
					$mask[] = $key;
				}
			}
		}

		return new self(
			array_intersect_key( $this->items, array_flip($mask) ) + $attributes
		);
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @param  callable  $callback
	 * @return self
	 */
	public function map(callable $callback)
	{
		$keys = array_keys($this->items);
		$items = array_map($callback, $this->items, $keys);
		return new self(array_combine($keys, $items));
	}

	/**
	 * Concatenate values of a given key as a string.
	 *
	 * @param  string  $value
	 * @param  string  $glue
	 * @return string
	 */
	public function implode($value, $glue = null)
	{
		$first = $this->first();
		if(is_array($first) || is_object($first))
		{
			return implode($glue, $this->pluck($value)->getAll());
		}
		return implode($value, $this->items);
	}

	/**
	 * GetTrait the first item from the collection.
	 *
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function first(callable $callback = null, $default = null)
	{
		return Arr::first($this->items, $callback, $default);
	}

	/**
	 * GetTrait the last item from the collection.
	 *
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function last(callable $callback = null, $default = null)
	{
		return Arr::last($this->items, $callback, $default);
	}

	/**
	 * GetTrait the values of a given key.
	 *
	 * @param  string|array  $value
	 * @param  string|null  $key
	 * @return self
	 */
	public function pluck($value, $key = null)
	{
		return new self(Arr::pluck($this->items, $value, $key));
	}

	/**
	 * Create a collection of all elements that do not pass a given truth test.
	 *
	 * @param  callable|mixed  $callback
	 * @return self
	 */
	public function reject($callback)
	{
		if($this->useAsCallable($callback))
		{
			return $this->filter(function ($value, $key) use ($callback) {
				return ! $callback($value, $key);
			});
		}

		return $this->filter(function ($item) use ($callback) {
			return $item != $callback;
		});
	}

	/**
	 * GetTrait the keys of the collection items.
	 *
	 * @return self
	 */
	public function keys()
	{
		return new self(array_keys($this->items));
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return self
	 */
	public function values()
	{
		return new self(array_values($this->items));
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}

	/**
	 * Determine if the collection is not empty.
	 *
	 * @return bool
	 */
	public function isNotEmpty()
	{
		return ! empty($this->items);
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->items);
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

	/**
	 * GetTrait the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array_map(static function ($value) {
			return $value instanceof Arrayable ? $value->toArray() : $value;
		}, $this->items);
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 * @param int $depth
	 * @return string
	 */
	public function toJson( $options = 0, $depth = 512 )
	{
		return Json::stringify($this->jsonSerialize(), $options, $depth);
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	function jsonSerialize()
	{
		return array_map(function ($value) {
			if($value instanceof JsonSerializable)
			{
				return $value->jsonSerialize();
			}
			else if($value instanceof Jsonable)
			{
				return Json::parse($this->toJson(), true);
			}
			else if($value instanceof Arrayable)
			{
				return $value->toArray();
			}
			else
			{
				return $value;
			}
		}, $this->items);
	}

	/**
	 * Results array of items from Collection or Arrayable.
	 *
	 * @param  mixed  $items
	 * @return array
	 */
	protected function getItems( $items )
	{
		if (is_array($items))
		{
			return $items;
		}
		if($items instanceof self)
		{
			return $items->getAll();
		}
		if($items instanceof Arrayable)
		{
			return $items->toArray();
		}
		if($items instanceof Jsonable)
		{
			return json_decode($items->toJson(), true);
		}
		if($items instanceof JsonSerializable)
		{
			return $items->jsonSerialize();
		}
		if($items instanceof Traversable)
		{
			return iterator_to_array($items);
		}

		return (array) $items;
	}

	/**
	 * Determine if the given value is callable, but not a string.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	protected function useAsCallable($value)
	{
		return ! is_string($value) && is_callable($value);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * @param array $an_array
	 * @return static
	 */
	public static function __set_state($an_array)
	{
		return new static($an_array["items"] ?? []);
	}
}