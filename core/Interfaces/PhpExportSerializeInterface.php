<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 6:44
 */

namespace EApp\Interfaces;

use EApp\Support\PhpExportSerialize;

interface PhpExportSerializeInterface
{
	public function phpExportSerialize(): PhpExportSerialize;
}