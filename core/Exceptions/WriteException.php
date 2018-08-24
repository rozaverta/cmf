<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.08.2018
 * Time: 17:42
 */

namespace EApp\Exceptions;

use ErrorException;
use Throwable;

class WriteException extends ErrorException
{
	public function __construct($message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Write error";
		}

		parent::__construct($message, $code, $severity, $filename, $line, $previous);
	}
}