<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace EApp\Event\Interfaces;

interface EventInterface
{
	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get all events parameters
	 *
	 * @return array
	 */
	public function getParams();

	/**
	 * Get event parameter by name
	 *
	 * @param string $name parameter name
	 * @return mixed
	 */
	public function getParam( $name );
}