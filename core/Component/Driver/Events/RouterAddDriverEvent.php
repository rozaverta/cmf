<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 0:33
 */

namespace EApp\Component\Driver\Events;


use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

class RouterAddDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, array $data = [] )
	{
		parent::__construct( $driver, "add", $data );
	}
}