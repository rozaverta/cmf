<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\View;

use EApp\Database\QueryPrototype;
use EApp\View\Scheme\TemplatePackagesSchemeDesigner;

class QueryPackages extends QueryPrototype
{
	protected $pagination = true;

	public function getTableName()
	{
		return 'template_packages';
	}

	protected function fetchObject()
	{
		return TemplatePackagesSchemeDesigner::class;
	}
}