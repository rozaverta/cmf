<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 12:18
 */

namespace EApp\Module\Driver\Events;

use EApp\Events\SystemDriverEvent;
use EApp\Interfaces\SystemDriverInterface;

/**
 * Class ConfigFileDriverEvent
 *
 * @package EApp\Component\Driver\Events
 */
class ConfigFileDriverEvent extends SystemDriverEvent
{
	private $aborted = false;

	public function __construct( SystemDriverInterface $driver )
	{
		parent::__construct( $driver, "update");
	}

	/**
	 * Abort writing
	 */
	public function abort()
	{
		$this->aborted = true;
		$this->stopPropagation();
	}

	/**
	 * The writing was aborted
	 *
	 * @return bool
	 */
	public function hasAborted(): bool
	{
		return $this->aborted;
	}
}