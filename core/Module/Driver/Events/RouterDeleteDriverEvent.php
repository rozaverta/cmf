<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 0:33
 */

namespace EApp\Module\Driver\Events;

use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

class RouterDeleteDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, array $data = [] )
	{
		parent::__construct( $driver, "delete", $data );
	}
}