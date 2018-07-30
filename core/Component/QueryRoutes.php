<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Component;

use EApp\DB\Query\Builder;
use EApp\DB\Query\JoinClause;
use EApp\DB\QueryPrototype;
use EApp\Component\Scheme\RouteSchemeDesigner;

class QueryRoutes extends QueryPrototype
{
	protected $check_type = false;

	protected function initialisation()
	{
		parent::initialisation();
		unset( $this->valid_columns["install"] );
		$this->columns = ["t.id", "t.module_id", "t.path", "t.properties"];
		$this->orderBy("position");
	}

	public function getTableName()
	{
		return "module_router";
	}

	protected function fetchObject()
	{
		return RouteSchemeDesigner::class;
	}

	protected function prepare( Builder $builder )
	{
		$builder
			->leftJoin("modules as m", function(JoinClause $join) {
				$join->on("m.id", "=", "t.module_id");
			})
			->whereNotNull("m.id")
			->where("m.install", true);

		return parent::prepare( $builder );
	}
}