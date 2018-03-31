<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Support\Traits;

use EApp\Support\Collection;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Interfaces\Jsonable;
use EApp\Support\Json;
use JsonSerializable;
use Traversable;

/**
 * @property array $items
 */
trait Set
{
	public function set( $name, $value = null )
	{
		if( func_num_args() == 1 )
		{
			if( ! is_array($name) && ! ($name instanceof Traversable) )
			{
				if($name instanceof Collection)
				{
					$name = $name->getAll();
				}
				else if($name instanceof Arrayable)
				{
					$name = $name->toArray();
				}
				else if($name instanceof Jsonable)
				{
					$name = Json::parse($name->toJson(), true);
				}
				else if($name instanceof JsonSerializable)
				{
					$name = $name->jsonSerialize();
				}
				else
				{
					$this->offsetSet(null, $name);
					return $this;
				}
			}

			$index = 0;

			foreach($name as $key => $value)
			{
				if( $key === $index )
				{
					++ $index;
					$this->offsetSet(null, $value);
				}
				else
				{
					$this->offsetSet($key, $value);
				}
			}
		}
		else
		{
			$this->offsetSet( $name, $value );
		}

		return $this;
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param  string|array  $keys
	 * @return $this
	 */
	public function forget($keys)
	{
		if( ! is_array($keys) )
		{
			if( func_num_args() == 1 )
			{
				$this->offsetUnset($keys);
				return $this;
			}
			else
			{
				$keys = func_get_args();
			}
		}

		foreach($keys as $name)
		{
			$this->offsetUnset($name);
		}

		return $this;
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param  mixed  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet( $offset, $value )
	{
		if (is_null($offset))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param  string  $offset
	 * @return void
	 */
	public function offsetUnset( $offset )
	{
		unset($this->items[$offset]);
	}
}
