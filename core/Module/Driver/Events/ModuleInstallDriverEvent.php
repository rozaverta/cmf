<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Module\Driver\Events;


use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

class ModuleInstallDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, array $data = [] )
	{
		parent::__construct( $driver, "install", $data );
	}
}