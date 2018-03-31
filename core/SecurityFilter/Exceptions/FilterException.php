<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.11.2017
 * Time: 18:24
 */

namespace EApp\SecurityFilter\Exceptions;

class FilterException extends \InvalidArgumentException
{
	public function __construct( $name, $message = "", $code = 0, $previous = null )
	{
		if( !$name )
		{
			$name = "Error";
		}
		if( !$message )
		{
			$message = "Invalid filter argument";
		}

		parent::__construct( $name . ": " . $message, $code, $previous );
	}
}