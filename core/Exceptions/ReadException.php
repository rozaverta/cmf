<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 3:19
 */

namespace EApp\Exceptions;

use ErrorException;
use Throwable;

class ReadException extends ErrorException
{
	public function __construct($message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Read error";
		}

		parent::__construct($message, $code, $severity, $filename, $line, $previous);
	}
}