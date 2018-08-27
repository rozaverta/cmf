<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Traits;

use EApp\Helper;

/**
 * @property array $items
 */
trait GetTrait
{
	protected $itemsGetUndefined = false;

	/**
	 * Get an item from the collection by key.
	 *
	 * @param  mixed  $name
	 * @return mixed
	 */
	public function get( $name )
	{
		if ($this->offsetExists($name))
		{
			return $this->items[$name];
		}
		return $this->itemsGetUndefined;
	}

	/**
	 * Get an item from the collection by keys.
	 *
	 * @param  array $keys
	 * @param bool $default default value
	 * @return mixed
	 */
	public function choice( array $keys, $default = false )
	{
		foreach( $keys as $key )
		{
			if( $this->offsetExists($key) )
			{
				return $this->items[$key];
			}
		}

		return $default;
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->items;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->items;
	}

	/**
	 * Get an item from the collection by key or default value if not exists.
	 *
	 * @param  mixed  $name
	 * @param  mixed  $default_value
	 * @return mixed
	 */
	public function getOr( $name, $default_value )
	{
		if ($this->offsetExists($name))
		{
			return $this->items[$name];
		}
		return Helper::value($default_value);
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  mixed  $name
	 * @return bool
	 */
	public function getIs( $name )
	{
		if( ! is_array($name) )
		{
			if( func_num_args() == 1 )
			{
				return $this->offsetExists($name);
			}
			else
			{
				$name = func_get_args();
			}
		}

		foreach($name as $value)
		{
			if (! $this->offsetExists($value))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function & offsetGet( $offset )
	{
		if( ! isset($this->items[$offset]) )
		{
			$value = null;
		}

		else if( is_array($this->items[$offset]) )
		{
			return $this->items[$offset];
		}

		else
		{
			$value = $this->items[$offset];
		}

		return $value;
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->items);
	}
}