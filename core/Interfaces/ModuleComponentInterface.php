<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:22
 */

namespace EApp\Interfaces;

use EApp\Module\Module;

interface ModuleComponentInterface
{
	/**
	 * Get module instance
	 *
	 * @return \EApp\Module\Module
	 */
	public function getModule(): Module;

	/**
	 * Get module id
	 *
	 * @return int
	 */
	public function getModuleId(): int;
}