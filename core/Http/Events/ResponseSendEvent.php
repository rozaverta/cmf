<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;

use EApp\Event\Interfaces\EventInterface;
use EApp\Event\EventParamTrait;
use EApp\Http\Response;

class ResponseSendEvent implements EventInterface
{
	use EventParamTrait;

	public function __construct( Response $response )
	{
		$this->params['response'] = $response;
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'onResponseSend';
	}
}