<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 17:25
 */

namespace EApp\Support;

class PhpExportSerialize
{
	protected $class_name;

	protected $method;

	protected $arguments = [];

	public function __construct( $object, $method, array $arguments = [] )
	{
		$this->class_name = is_object($object) ? get_class($object) : $object;
		$this->method = $method;
		$this->arguments = $arguments;
	}

	public static function __set_state( $data )
	{
		$ref = new \ReflectionClass($data["class_name"]);
		if($data["method"] === "__construct")
		{
			return $ref->newInstanceArgs($data["arguments"]);
		}

		$method = $ref->getMethod($data["method"]);
		if($method->isStatic())
		{
			return $method->invokeArgs(null, $data["arguments"]);
		}
		else
		{
			return $method->invokeArgs($ref->newInstanceWithoutConstructor(), $data["arguments"]);
		}
	}
}