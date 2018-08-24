<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace EApp\Component\Driver\Events;

use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

class DataBaseCreateTableDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, array $data = [] )
	{
		parent::__construct( $driver, "create", $data );
	}
}