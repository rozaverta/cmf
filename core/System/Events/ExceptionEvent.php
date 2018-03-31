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

/**
 * Class ExceptionEvent
 *
 * @property \Exception | \Error $exception
 * @property \EApp\App $app
 * @property boolean $error
 */
final class ExceptionEvent implements EventInterface
{
	use EventParamTrait;

	public function __construct( App $app, $error )
	{
		$this->params['app'] = $app;
		$this->params['exception'] = $error;
		$this->params['error'] = PHP_VERSION >= 7 && $error instanceof \Error;
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'onSystemException';
	}
}