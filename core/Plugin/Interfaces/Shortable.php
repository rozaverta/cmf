<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.09.2017
 * Time: 13:26
 */

namespace EApp\Plugin\Interfaces;

interface Shortable
{
	/**
	 * The plugin used a short tag
	 *
	 * @return mixed
	 */
	public function toShortTag();

	/**
	 * Does the plugin use a short tag?
	 * @return boolean
	 */
	public function hasShortTag();
}