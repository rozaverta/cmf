<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;
use EApp\Event\EventParamTrait;
use EApp\Event\Interfaces\EventInterface;

abstract class SystemEvent implements EventInterface
{
	use EventParamTrait;

	public function __construct( App $app )
	{
		$this->params['app'] = $app;
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'onSystem';
	}
}