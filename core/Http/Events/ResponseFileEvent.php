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

class ResponseFileEvent extends ResponseSendEvent implements EventInterface
{
	public function __construct( Response $response, $file, $filename, $mime_type )
	{
		$this->params = compact('response', 'file', 'filename', 'mime_type');
	}
}