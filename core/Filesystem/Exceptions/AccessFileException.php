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

class AccessFileException extends AccessException
{
	use FileExceptionTrait;

	public function __construct( $file, $message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, Throwable $previous = null )
	{
		$this->setPathname($file);
		if( !strlen($message) )
		{
			$message = "The '{$file}' file access error";
		}
		parent::__construct( $message, $code, $severity, $filename, $line, $previous );
	}
}