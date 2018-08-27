<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:29
 */

namespace EApp\Interfaces;

interface Arrayable
{
	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray();
}