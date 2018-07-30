<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace EApp\Component\Driver\Events;

use EApp\System\Events\DriverSystemEvent;
use EApp\System\Interfaces\SystemDriver;

class DataBaseDropTableEvent extends DriverSystemEvent
{
	public function __construct( SystemDriver $driver, array $data = [] )
	{
		parent::__construct( $driver, "drop", $data );
	}
}