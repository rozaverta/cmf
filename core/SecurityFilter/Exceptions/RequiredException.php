<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.11.2017
 * Time: 18:24
 */

namespace EApp\SecurityFilter\Exceptions;

class RequiredException extends ValidateException
{
	public function __construct( $name, $message = "", $code = 0, $previous = null )
	{
		if( !$message )
		{
			$message = "Required field";
		}

		parent::__construct( $name, $message, $code, $previous );
	}
}