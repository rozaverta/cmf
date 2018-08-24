<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 21:04
 */

namespace EApp\Filesystem\Exceptions;

use EApp\Exceptions\AccessException;
use Throwable;

class AccessPathException extends AccessException
{
	use PathExceptionTrait;

	public function __construct( $path, $message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, Throwable $previous = null )
	{
		$this->setPath($path);
		if( !strlen($message) )
		{
			$message = "The '{$path}' path access error";
		}
		parent::__construct( $message, $code, $severity, $filename, $line, $previous );
	}
}