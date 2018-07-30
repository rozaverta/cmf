<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.01.2018
 * Time: 16:25
 */

namespace EApp;

use EApp\Component\Module;

class ModuleCore extends Module
{
	public function __construct()
	{
		parent::__construct(0, false );
	}

	protected function fetch()
	{
		return $this->load( $this->id, new ModuleCoreConfig() );
	}
}