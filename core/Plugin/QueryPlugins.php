<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 23:21
 */

namespace EApp\Plugin;

use EApp\Database\QueryPrototype;
use EApp\Plugin\Scheme\PluginSchemeDesigner;

class QueryPlugins extends QueryPrototype
{
	protected $pagination = true;

	public function getTableName()
	{
		return 'plugins';
	}

	protected function initialisation()
	{
		parent::initialisation();
		$this->orderBy("name");
	}

	protected function fetchObject()
	{
		return PluginSchemeDesigner::class;
	}
}