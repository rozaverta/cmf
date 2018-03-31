<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 22:11
 */

namespace EApp\Config;

use EApp\Config\Scheme\PropertySchemeDesigner;
use EApp\DB\QueryPrototype;

class QueryConfig extends QueryPrototype
{
	public function getTableName()
	{
		return 'config';
	}

	protected function fetchObject()
	{
		return PropertySchemeDesigner::class;
	}
}