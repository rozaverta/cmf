<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 15:23
 */

namespace EApp\Http\Collections;

use EApp\Support\Collection;
use EApp\Support\Str;

class HeaderCollection extends Collection
{
	protected static $header_rename =
		[
			"te" => "TE",
			"content-md5" => "Content-MD5",
			"etag" => "ETag",
			"mime-version" => "MIME-Version",
			"www-authenticate" => "WWW-Authenticate",
		];

	public function __construct( $items )
	{
		// normalize keys
		foreach( $this->getItems($items) as $key => $value )
		{
			$key = self::normalizeKey($key);
			if( $key !== false )
			{
				$this->offsetSet($key, $value);
			}
		}
	}

	public static function normalizeKey( $name )
	{
		if( ! is_string($name) )
		{
			return false;
		}

		$name = trim($name);
		if( !strlen($name) || is_numeric($name[0]) )
		{
			return false;
		}

		$name = strtolower($name);
		return isset(self::$header_rename[$name]) ? self::$header_rename[$name] : Str::title($name);
	}

	/**
	 * Get an item from the collection by key.
	 *
	 * @param  mixed  $name
	 * @return mixed
	 */
	public function get( $name )
	{
		$name = self::normalizeKey($name);
		if(parent::offsetExists($name))
		{
			return $this->items[$name];
		}
		return $this->itemsGetUndefined;
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
		$name = self::normalizeKey($name);
		if(parent::offsetExists($name))
		{
			return $this->items[$name];
		}
		return \E\Value($default_value);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function & offsetGet( $offset )
	{
		return parent::offsetGet( self::normalizeKey($offset) );
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		$offset = self::normalizeKey($offset);
		return $offset === false ? false : parent::offsetExists($offset);
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
		$offset = self::normalizeKey($offset);
		if( $offset !== false )
		{
			$this->items[$offset] = $value;
		}
	}
}