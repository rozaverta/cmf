<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 23:40
 */

namespace EApp\Support;

use ArrayAccess;
use EApp\Helper;
use InvalidArgumentException;

class Arr
{
	/**
	 * Determine whether the given value is array accessible.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public static function accessible($value)
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Add an element to an array using "dot" notation if it doesn't exist.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function add($array, $key, $value)
	{
		if (is_null(static::get($array, $key)))
		{
			static::set($array, $key, $value);
		}
		return $array;
	}

	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null)
	{
		if (! static::accessible($array))
		{
			return Helper::value($default);
		}
		if (is_null($key))
		{
			return $array;
		}
		if (static::exists($array, $key))
		{
			return $array[$key];
		}
		if (strpos($key, '.') === false)
		{
			return isset($array[$key]) ? $array[$key] : Helper::value($default);
		}

		foreach (explode('.', $key) as $segment)
		{
			if (static::accessible($array) && static::exists($array, $segment))
			{
				$array = $array[$segment];
			}
			else
			{
				return Helper::value($default);
			}
		}

		return $array;
	}

	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return array
	 */
	public static function except($array, $keys)
	{
		static::forget($array, $keys);
		return $array;
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param  array  $array
	 * @param  array|string  $keys
	 * @return void
	 */
	public static function forget(& $array, $keys)
	{
		$original = & $array;
		$keys = (array) $keys;
		$depth = false;

		if (count($keys) === 0)
		{
			return;
		}

		foreach ($keys as $key)
		{
			if( $depth )
			{
				$array = & $original;
				$depth = false;
			}

			// if the exact key exists in the top-level, remove it
			if( ! static::exists($array, $key))
			{
				// else find key
				for( $i = 0, $parts = explode('.', $key), $count = count($parts); $i < $count; $i++ )
				{
					$key = $parts[$i];
					if( $i + 1 == $count )
					{
						break;
					}

					if( isset($array[$key]) && is_array($array[$key]))
					{
						$array = & $array[$key];
						$depth = true;
					}
					else
					{
						continue 2;
					}
				}
			}

			unset($array[$key]);
		}
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function set( & $array, $key, $value)
	{
		if(is_null($key))
		{
			return $array = $value;
		}

		if(strpos($key, ".") !== false)
		{
			for( $i = 0, $keys = explode('.', $key), $count = count($keys); $i < $count; $i++ )
			{
				$key = $keys[$i];
				if( $i + 1 == $count )
				{
					break;
				}

				// If the key doesn't exist at this depth, we will just create an empty array
				// to hold the next value, allowing us to create the arrays to hold final
				// values at the correct depth. Then we'll keep digging into the array.
				if(! isset($array[$key]) || ! is_array($array[$key]))
				{
					$array[$key] = [];
				}

				$array = & $array[$key];
			}
		}

		$array[$key] = $value;

		return $array;
	}

	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|array  $keys
	 * @return bool
	 */
	public static function has($array, $keys)
	{
		if(is_null($keys) || ! $array)
		{
			return false;
		}

		$keys = (array) $keys;
		if($keys === [])
		{
			return false;
		}

		foreach($keys as $key)
		{
			if(static::exists($array, $key))
			{
				continue;
			}

			if( strpos($key, ".") !== false )
			{
				$subKeyArray = $array;
				for($i = 0, $map = explode('.', $key), $len = count($map); $i < $len; $i++)
				{
					$segment = $map[$i];
					if( ($i < 1 || static::accessible($subKeyArray)) && static::exists($subKeyArray, $segment) )
					{
						if( $i + 1 < $len )
						{
							$subKeyArray = $subKeyArray[$segment];
						}
					}
					else
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Determine if the given key exists in the provided array.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|int  $key
	 * @return bool
	 */
	public static function exists($array, $key)
	{
		if($array instanceof ArrayAccess)
		{
			return $array->offsetExists($key);
		}
		return array_key_exists($key, $array);
	}

	/**
	 * Collapse an array of arrays into a single array.
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function collapse($array)
	{
		$results = [];
		foreach ($array as $values)
		{
			if ($values instanceof Collection)
			{
				$values = $values->getAll();
			}
			elseif (! is_array($values))
			{
				continue;
			}
			$results = array_merge($results, $values);
		}
		return $results;
	}

	/**
	 * Shuffle the given array and return the result.
	 *
	 * @param  array  $array
	 * @return array
	 */
	public static function shuffle($array)
	{
		shuffle($array);
		return $array;
	}

	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param  array  $array
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function first($array, callable $callback = null, $default = null)
	{
		if (is_null($callback))
		{
			if (empty($array))
			{
				return Helper::value($default);
			}
			foreach ($array as $item)
			{
				return $item;
			}
		}

		foreach ($array as $key => $value) {
			if (call_user_func($callback, $value, $key))
			{
				return $value;
			}
		}

		return Helper::value($default);
	}

	/**
	 * Return the last element in an array passing a given truth test.
	 *
	 * @param  array  $array
	 * @param  callable|null  $callback
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function last($array, callable $callback = null, $default = null)
	{
		if (is_null($callback))
		{
			return empty($array) ? Helper::value($default) : end($array);
		}
		return static::first(array_reverse($array, true), $callback, $default);
	}

	/**
	 * Pluck an array of values from an array.
	 *
	 * @param  array  $array
	 * @param  string|array  $value
	 * @param  string|array|null  $key
	 * @return array
	 */
	public static function pluck($array, $value, $key = null)
	{
		$results = [];
		list($value, $key) = static::explodePluckParameters($value, $key);
		foreach ($array as $item)
		{
			$itemValue = Helper::dataGet($item, $value);
			// If the key is "null", we will just append the value to the array and keep
			// looping. Otherwise we will key the array using the value of the key we
			// received from the developer. Then we'll return the final array form.

			if(is_null($key))
			{
				$results[] = $itemValue;
			}
			else
			{
				$itemKey = Helper::dataGet($item, $key);
				if(is_object($itemKey) && method_exists($itemKey, '__toString')) {
					$itemKey = (string) $itemKey;
				}
				$results[$itemKey] = $itemValue;
			}
		}
		return $results;
	}

	/**
	 * Get one or a specified number of random values from an array.
	 *
	 * @param  array  $array
	 * @param  int|null  $number
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function random($array, $number = null)
	{
		$requested = is_null($number) ? 1 : $number;
		$count = count($array);

		if ($requested > $count)
		{
			throw new InvalidArgumentException(
				"You requested {$requested} items, but there are only {$count} items available."
			);
		}

		if (is_null($number)) {
			return $array[array_rand($array)];
		}

		if ((int) $number === 0) {
			return [];
		}

		$keys = array_rand($array, $number);
		$results = [];
		foreach ((array) $keys as $key)
		{
			$results[] = $array[$key];
		}

		return $results;
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param  array  $array
	 * @param  int  $depth
	 * @return array
	 */
	public static function flatten($array, $depth = INF)
	{
		return array_reduce($array, function ($result, $item) use ($depth) {
			$item = $item instanceof Collection ? $item->getAll() : $item;
			if (! is_array($item))
			{
				return array_merge($result, [$item]);
			}
			else if($depth === 1) {
				return array_merge($result, array_values($item));
			}
			else
			{
				return array_merge($result, static::flatten($item, $depth - 1));
			}
		}, []);
	}

	/**
	 * If the given value is not an array, wrap it in one.
	 *
	 * @param  mixed  $value
	 * @return array
	 */
	public static function wrap($value)
	{
		return ! is_array($value) ? [$value] : $value;
	}

	/**
	 * Explode the "value" and "key" arguments passed to "pluck".
	 *
	 * @param  string|array  $value
	 * @param  string|array|null  $key
	 * @return array
	 */
	protected static function explodePluckParameters($value, $key)
	{
		$value = is_string($value) ? explode('.', $value) : $value;
		$key = is_null($key) || is_array($key) ? $key : explode('.', $key);
		return [$value, $key];
	}
}