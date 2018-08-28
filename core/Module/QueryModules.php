<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Module;

use EApp\Schemes\ModulesSchemeDesigner;
use EApp\Database\QueryPrototype;

class QueryModules extends QueryPrototype
{
	protected $pagination = true;

	protected function initialisation()
	{
		parent::initialisation();
		$this->orderBy("name");
	}

	public function getTableName()
	{
		return "modules";
	}

	protected function fetchObject()
	{
		return ModulesSchemeDesigner::class;
	}
}