<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\Event\Event;

/**
 * Class ThrowableEvent
 *
 * @property \Exception | \Error $exception
 * @property \EApp\App $app
 * @property boolean $error
 */
final class ThrowableEvent extends Event
{
	public function __construct( \Throwable $throwable )
	{
		parent::__construct('onSystemException', [
			"app" => "",
			"throwable" => $throwable,
			"error" => $throwable instanceof \Error
		]);
	}
}