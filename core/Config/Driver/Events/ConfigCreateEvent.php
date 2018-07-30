<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:06
 */

namespace EApp\Config\Driver\Events;

use EApp\System\Events\DriverSystemEvent;
use EApp\System\Interfaces\SystemDriver;

class ConfigCreateEvent extends DriverSystemEvent
{
	public function __construct( SystemDriver $driver, $name, array $data = [] )
	{
		parent::__construct( $driver, "create", ["name" => $name, "data" => $data] );
	}
}