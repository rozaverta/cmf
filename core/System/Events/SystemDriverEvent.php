<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\System\Interfaces\SystemDriver;

abstract class SystemDriverEvent extends SystemEvent
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
	 * SystemDriverEvent constructor.
	 *
	 * @param SystemDriver $driver
	 * @param string $action
	 * @param array $params
	 */
	public function __construct( SystemDriver $driver, string $action, array $params = [] )
	{
		parent::__construct($params);

		$this->driver = $driver;
		$this->driver_action = $action;
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