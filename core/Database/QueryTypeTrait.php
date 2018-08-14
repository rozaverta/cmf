<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 07.09.2017
 * Time: 23:24
 */

namespace EApp\Database;

trait QueryTypeTrait
{
	protected $fields_number = [];
	protected $fields_boolean = [];
	protected $fields_datetime = [];
	protected $check_type = true;
	protected $use_type = false;

	protected function setType( $type, $name )
	{
		if( $type == "boolean" ) $this->fields_boolean[] = $name;
		else if( $type == "integer" || $type == "number" ) $this->fields_number[] = $name;
		else if( $type == "datetime" ) $this->fields_datetime[] = $name;
		else return;

		$this->use_type = true;
	}
}