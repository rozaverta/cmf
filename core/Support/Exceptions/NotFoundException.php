<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 3:19
 */

namespace EApp\Support\Exceptions;

use Exception;

class NotFoundException extends Exception
{
	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Not found.";
		}

		parent::__construct( $message, $code, $previous );
	}
}