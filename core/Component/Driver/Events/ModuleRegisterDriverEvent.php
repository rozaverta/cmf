<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace EApp\Component\Driver\Events;

use EApp\System\Events\SystemDriverEvent;
use EApp\System\Fs\FileResource;
use EApp\System\Interfaces\SystemDriver;

/**
 * Class ModuleRegisterDriverEvent
 *
 * @property string $module_name
 * @property string $name_space
 * @property FileResource $manifest
 *
 * @package EApp\Component\Driver\Events
 */
class ModuleRegisterDriverEvent extends SystemDriverEvent
{
	public function __construct( SystemDriver $driver, string $module_name, string $name_space, FileResource $manifest )
	{
		parent::__construct( $driver, "register", compact('module_name', 'name_space', 'manifest') );
	}
}