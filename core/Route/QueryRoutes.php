<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Route;

use EApp\Database\Query\Builder;
use EApp\Database\Query\JoinClause;
use EApp\Database\QueryPrototype;
use EApp\Schemes\ModuleRouterSchemeDesigner;

class QueryRoutes extends QueryPrototype
{
	protected $check_type = false;

	protected function initialisation()
	{
		parent::initialisation();
		unset( $this->valid_columns["install"] );
		$this->columns = ["t.id", "t.module_id", "t.type", "t.rule", "t.properties"];
		$this->orderBy("position");
	}

	public function getTableName()
	{
		return "module_router";
	}

	protected function fetchObject()
	{
		return ModuleRouterSchemeDesigner::class;
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