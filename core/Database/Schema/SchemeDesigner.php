<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2018
 * Time: 18:50
 */

namespace EApp\Database\Schema;

use EApp\Support\Interfaces\Arrayable;
use JsonSerializable;

class SchemeDesigner implements SchemeDesignerInterface, Arrayable, JsonSerializable
{
	public function __set_state( $data )
	{
		$ref = new \ReflectionClass( static::class );
		$instance = $ref->newInstanceWithoutConstructor();

		foreach( $data as $key => $value )
		{
			$instance->{$key} = $value;
		}

		return $instance;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return get_object_vars( $this );
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}