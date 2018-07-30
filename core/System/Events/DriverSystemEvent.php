<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;
use EApp\System\Interfaces\SystemDriver;

abstract class DriverSystemEvent extends SystemEvent
{
	/**
	 * @var SystemDriver
	 */
	protected $driver;

	/**
	 * @var string
	 */
	protected $driver_action;

	/**
	 * DriverSystemEvent constructor.
	 * @param SystemDriver $driver
	 * @param string $action
	 * @param array $prop
	 */
	public function __construct( SystemDriver $driver, $action, array $prop = [] )
	{
		parent::__construct(App::getInstance());

		$this->driver = $driver;
		$this->driver_action = $action;

		foreach(array_keys($prop) as $key)
		{
			$this->params[$key] = $prop[$key];
		}
	}

	public function getDriver()
	{
		return $this->driver;
	}

	public function getDriverAction()
	{
		return $this->driver_action;
	}
}