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

/**
 * Class ThrowableEvent
 *
 * @property \Exception | \Error $throwable
 * @property \EApp\App $app
 * @property boolean $error
 */
final class ThrowableEvent extends Event
{
	public function __construct( \Throwable $throwable )
	{
		parent::__construct('onThrowable', [
			"app" => App::getInstance(),
			"throwable" => $throwable,
			"error" => $throwable instanceof \Error
		]);
	}
}