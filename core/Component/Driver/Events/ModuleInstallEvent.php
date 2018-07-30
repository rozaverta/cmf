<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Component\Driver\Events;


use EApp\System\Events\DriverSystemEvent;
use EApp\System\Interfaces\SystemDriver;

class ModuleInstallEvent extends DriverSystemEvent
{
	public function __construct( SystemDriver $driver, array $data = [] )
	{
		parent::__construct( $driver, "install", $data );
	}
}