<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 3:19
 */

namespace EApp\Exceptions;

use Exception;

class PageNotFoundException extends NotFoundException
{
	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		if( !strlen($message) )
		{
			$message = 'Page not found.';
		}
		if( !$code )
		{
			$code = 404;
		}

		parent::__construct( $message, $code, $previous );
	}
}