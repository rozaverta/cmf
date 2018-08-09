<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Component\Driver\Events;

use EApp\System\Events\SystemDriverEvent;
use EApp\System\Interfaces\SystemDriver;

class ModuleAddDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriver $driver, array $data = [] )
	{
		parent::__construct( $driver, "add", $data );
	}
}