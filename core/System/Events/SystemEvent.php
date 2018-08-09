<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;
use EApp\Event\Event;

/**
 * Abstract class SystemEvent
 *
 * @property \EApp\App $app
 */
abstract class SystemEvent extends Event
{
	public function __construct(array $params = [])
	{
		$params["app"] = App::getInstance();
		parent::__construct("onSystem", $params);
	}
}