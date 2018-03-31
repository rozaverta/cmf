<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:31
 */

namespace EApp\Support\Interfaces;


interface Jsonable
{
	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 * @param int $depth
	 * @return string
	 */
	public function toJson($options = 0, $depth = 512);
}