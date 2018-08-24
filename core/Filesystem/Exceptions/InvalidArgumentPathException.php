<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:30
 */

namespace EApp\Filesystem\Exceptions;

use Throwable;

class InvalidArgumentPathException extends \InvalidArgumentException
{
	use PathExceptionTrait;

	public function __construct( $path, $message = "", $code = 0, Throwable $previous = null )
	{
		$this->setPath($path);
		parent::__construct( $message, $code, $previous );
	}
}