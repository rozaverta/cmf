<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:00
 */

namespace EApp\Route;

use EApp\Interfaces\TypeOfInterface;
use EApp\Route\Interfaces\RuleInterface;
use EApp\Support\Collection;

class RuleCollection extends Collection implements TypeOfInterface
{
	public function typeOf( & $value, $name = null ): bool
	{
		return is_null($name) && $value instanceof RuleInterface;
	}
}