<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;

use EApp\Event\Event;
use EApp\Http\Response;

/**
 * Class ResponseSendEvent
 *
 * @property Response $response
 *
 * @package EApp\Http\Events
 */
class ResponseSendEvent extends Event
{
	public function __construct( Response $response )
	{
		parent::__construct('onResponseSend', compact('response'));
	}
}