<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 0:33
 */

namespace EApp\Component\Driver\Events;

use EApp\System\Events\DriverSystemEvent;
use EApp\System\Interfaces\SystemDriver;

class EditRouterEvent extends DriverSystemEvent
{
	public function __construct( SystemDriver $driver, array $data = [] )
	{
		parent::__construct( $driver, "edit", $data );
	}
}