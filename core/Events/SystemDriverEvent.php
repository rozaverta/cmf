<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

use EApp\App;
use EApp\Event\Event;
use EApp\Interfaces\SystemDriverInterface;

/**
 * Class SystemDriverEvent
 *
 * @property \EApp\App $app
 *
 * @package EApp\Events
 */
abstract class SystemDriverEvent extends Event
{
	/**
	 * @var SystemDriverInterface
	 */
	protected $driver;

	/**
	 * @var string
	 */
	protected $driver_action;

	/**
	 * SystemDriverEvent constructor.
	 *
	 * @param SystemDriverInterface $driver
	 * @param string $action
	 * @param array $params
	 */
	public function __construct( SystemDriverInterface $driver, string $action, array $params = [] )
	{
		$params["app"] = App::getInstance();
		parent::__construct("onSystemDriver", $params);

		$this->driver = $driver;
		$this->driver_action = $action;
	}

	/**
	 * @return SystemDriverInterface
	 */
	public function getDriver(): SystemDriverInterface
	{
		return $this->driver;
	}

	/**
	 * @return string
	 */
	public function getDriverAction(): string
	{
		return $this->driver_action;
	}
}