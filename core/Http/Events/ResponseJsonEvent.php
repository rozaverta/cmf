<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;

use EApp\Event\Interfaces\EventInterface;
use EApp\Http\Response;

class ResponseJsonEvent extends ResponseSendEvent implements EventInterface
{
	public function __construct( Response $response, $json, $prefix )
	{
		$this->params = compact('response', 'json', 'prefix');
	}

	public function setJson( $object )
	{
		if( is_object($object) || is_array($object) )
		{
			$this->params['object'] = $object;
		}
	}
}
