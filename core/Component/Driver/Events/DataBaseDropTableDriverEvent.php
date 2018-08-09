<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace EApp\Component\Driver\Events;

use EApp\System\Events\SystemDriverEvent;
use EApp\System\Interfaces\SystemDriver;

class DataBaseDropTableDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriver $driver, array $data = [] )
	{
		parent::__construct( $driver, "drop", $data );
	}
}