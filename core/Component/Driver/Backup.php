<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Component\Module;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;

class Backup implements SystemDriver, Loggable
{
	const BACKUP_ASSETS = 1;
	const BACKUP_APPLICATION = 2;
	const BACKUP_DATABASE_SCHEME = 4;
	const BACKUP_DATABASE_DATA = 8;
	const BACKUP_MODULES = 16; // only for ModuleCore

	use LoggableTrait;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	public function __construct( Module $module )
	{
		$this->module = $module;
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function create( $flag = null, $directories = [] )
	{
		// todo
	}

	public function restore( $time )
	{
		// todo
	}

	public function remove( $time )
	{
		// todo
	}
}
