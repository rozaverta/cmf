<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:56
 */

namespace EApp\Filesystem\Exceptions;

use EApp\Exceptions\WriteException;
use Throwable;

class WriteFileException extends WriteException
{
	use FileExceptionTrait;

	public function __construct(string $path, $message = "", $code = 0, $severity = 1, $filename = __FILE__, $line = __LINE__, Throwable $previous = null)
	{
		$this->setPathname($path);

		if( !strlen($message) )
		{
			$message = "Cannot write the '{$path}' file data";
		}

		parent::__construct($message, $code, $severity, $filename, $line, $previous);
	}
}