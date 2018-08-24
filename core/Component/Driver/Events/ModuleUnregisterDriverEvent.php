<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Component\Driver\Events;

use EApp\Component\Module;
use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

/**
 * Class ModuleUnregisterDriverEvent
 *
 * @property Module $module
 *
 * @package EApp\Component\Driver\Events
 */
class ModuleUnregisterDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, Module $module )
	{
		parent::__construct( $driver, "unregister", compact('module') );
	}
}