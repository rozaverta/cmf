<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 23:22
 */

namespace EApp\Filesystem;

use EApp\Support\Collection;
use EApp\Interfaces\TypeOfInterface;

class SplFileCollection extends Collection implements TypeOfInterface
{
	public function typeOf( & $value, $name = null ): bool
	{
		return is_null($name) && $value instanceof \SplFileInfo;
	}
}