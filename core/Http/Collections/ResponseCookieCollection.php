<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 15:23
 */

namespace EApp\Http\Collections;

use EApp\Http\ResponseCookie;
use EApp\Support\Collection;

class ResponseCookieCollection extends Collection
{
	public function __construct($items = [])
	{
		foreach( $this->getItems($items) as $key => $item )
		{
			$this->offsetSet($key, $item);
		}
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
		if (!$value instanceof ResponseCookie)
		{
			$value = new ResponseCookie($offset, $value);
		}
		parent::offsetSet($offset, $value);
	}
}