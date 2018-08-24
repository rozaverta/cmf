<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 21:57
 */

namespace EApp\Filesystem\Exceptions;

use EApp\Exceptions\NotFoundException;
use Throwable;

class NotFoundFileException extends NotFoundException
{
	use FileExceptionTrait;

	public function __construct($path, $message = "", $code = 0, Throwable $previous = null)
	{
		$this->setPathname($path);

		if( !strlen($message) )
		{
			$message = "The '{$path}' file not found";
		}

		parent::__construct( $message, $code, $previous );
	}
}