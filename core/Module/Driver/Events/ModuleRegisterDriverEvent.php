<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Module\Driver\Events;

use EApp\Events\SystemDriverEvent;
use EApp\Filesystem\Resource;
use EApp\Interfaces\SystemDriverInterface;

/**
 * Class ModuleRegisterDriverEvent
 *
 * @property string $module_name
 * @property string $name_space
 * @property \EApp\Filesystem\Resource $manifest
 *
 * @package EApp\Component\Driver\Events
 */
class ModuleRegisterDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriverInterface $driver, string $module_name, string $name_space, Resource $manifest )
	{
		parent::__construct( $driver, "register", compact('module_name', 'name_space', 'manifest') );
	}
}