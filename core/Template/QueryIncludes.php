<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\Template;

use EApp\DB\Query\Builder;
use EApp\DB\Query\JoinClause;
use EApp\DB\QueryPrototype;
use EApp\Template\Scheme\IncludeSchemeDesigner;

class QueryIncludes extends QueryPrototype
{
	protected $pagination = true;

	public function getTableName()
	{
		return 'template_includes';
	}

	protected function fetchObject()
	{
		return IncludeSchemeDesigner::class;
	}

	protected function initialisation()
	{
		parent::initialisation();

		$this->valid_columns['module_id'] = 'm.id';
		$this->valid_columns['module_name'] = 'm.name';
		$this->columns[] = 'm.name as module_name';

		$this
			->orderBy('module_name')
			->orderBy('position');
	}

	protected function prepare( Builder $builder )
	{
		$builder
			->leftJoin('modules as m', function(JoinClause $join) {
				$join->on('t.module_id', '=', 'm.id');
			})
			->where("m.install", true);

		return parent::prepare( $builder );
	}
}