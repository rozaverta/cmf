<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:22
 */

namespace EApp\System\Interfaces;

interface SystemDriver
{
	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule();
}