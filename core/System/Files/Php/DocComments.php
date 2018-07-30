<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2017
 * Time: 23:43
 */

namespace EApp\System\Files\Php;

use EApp\Support\Traits\Get;

class DocComments
{
	use Get;

	protected $items = [];
	private $class_name;
	private $method_name = null;

	public function __construct( $class_name, $method = null )
	{
		$rc = is_object($class_name) && $class_name instanceof \ReflectionClass ? $class_name : new \ReflectionClass($class_name);
		$this->class_name = $rc->getName();

		if( $method )
		{
			$rc = $rc->getMethod($method);
			$this->method_name = $rc->getName();
		}

		$this->items = $this->getComments($rc->getDocComment());
	}

	public function getClassName()
	{
		return $this->class_name;
	}

	public function getMethodName()
	{
		return $this->method_name;
	}

	private function getComments( $text )
	{
		$text = str_replace(["\r\n", "\r"], "\n", $text);
		$text = trim($text);

		$get = [];

		$key = 0;
		$name = false;

		foreach( explode("\n", $text) as $line )
		{
			$line = trim($line);
			if( strlen($line) )
			{
				$l2 = substr($line, 0, 2);
				if( $l2 === "/*" || $l2 == "//" )
				{
					$line = ltrim( substr($line, 2) );
				}

				while( strlen($line) && $line[0] === "*" )
				{
					$line = substr($line, 1);
				}

				$line = ltrim($line);
			}

			if( ! strlen($line) )
			{
				$name = false;
				continue;
			}

			if( preg_match('/^@(.+?)(?:\s+|$)/', $line, $m) )
			{
				$name = $m[1];
				$line = substr($line, strlen($m[0]));
			}
			else if( $name === false )
			{
				$name = $key ++;
			}

			if( !isset($get[$name]) || !strlen($get[$name]) )
			{
				$get[$name] = $line;
			}
			else if( strlen($line) )
			{
				$get[$name] .= " " . $line;
			}
		}

		return $get;
	}
}