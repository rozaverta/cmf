<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:29
 */

namespace EApp\System\Interfaces;

interface ControllerContentOutput
{
	/**
	 * Render content is raw output.
	 *
	 * @return boolean
	 */
	public function isRaw();

	/**
	 * Render content.
	 *
	 * @return void
	 */
	public function output();
}