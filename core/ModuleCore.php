<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.01.2018
 * Time: 16:25
 */

namespace EApp;

use EApp\Module\Module;

class ModuleCore extends Module
{
	public function __construct()
	{
		parent::__construct(0);
	}

	protected function fetch( int $id )
	{
		$module_config = new ModuleCoreConfig();
		$version = Prop::cache("system")->getOr("version", $module_config->version);
		if( $this->is_install && $module_config->version !== $version )
		{
			throw new \InvalidArgumentException("The current version of the system does not match the previously specified");
		}

		return $this->load( $id, $module_config, $version );
	}
}