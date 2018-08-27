<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 11:25
 */

namespace EApp\Schemes;

use EApp\Component\Module;
use EApp\Database\Schema\SchemeDesigner;
use EApp\Interfaces\ModuleComponentInterface;
use EApp\Traits\GetModuleComponentTrait;

abstract class _ModuleSchemeDesigner extends SchemeDesigner implements ModuleComponentInterface
{
	use GetModuleComponentTrait {
		getModuleId as getNativeModuleId;
	}

	/**
	 * ModuleConfig identifier.
	 *
	 * @var int
	 */
	public $module_id;

	public function getModuleId(): int
	{
		return $this->hasModule() ? $this->getNativeModuleId() : $this->module_id;
	}

	protected function reloadModule()
	{
		$this->setModule(Module::cache($this->module_id));
	}
}