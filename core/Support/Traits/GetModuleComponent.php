<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace EApp\Support\Traits;

use EApp\Component\Module;

trait GetModuleComponent
{
	/**
	 * @var Module | null
	 */
	protected $module;

	/**
	 * Get module component
	 *
	 * @return Module
	 */
	public function getModule(): Module
	{
		return $this->module;
	}

	/**
	 * Get module id
	 *
	 * @return int
	 */
	public function getModuleId(): int
	{
		return $this->module->getId();
	}

	/**
	 * @return bool
	 */
	public function hasModule(): bool
	{
		return ! is_null($this->module);
	}

	/**
	 * @param Module $module
	 * @return $this
	 */
	protected function setModule( Module $module )
	{
		$this->module = $module;
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function unsetModule()
	{
		$this->module = null;
		return $this;
	}
}