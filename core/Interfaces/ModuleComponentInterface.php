<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:22
 */

namespace EApp\Interfaces;

use EApp\Component\Module;

interface ModuleComponentInterface
{
	/**
	 * GetTrait module instance
	 *
	 * @return \EApp\Component\Module
	 */
	public function getModule(): Module;

	/**
	 * GetTrait module id
	 *
	 * @return int
	 */
	public function getModuleId(): int;
}