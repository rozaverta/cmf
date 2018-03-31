<?php
/**
 * Created by IntelliJ IDEA.
 * User: gosha
 * Date: 16.02.2017
 * Time: 20:26
 */

namespace EApp;

use EApp\Support\Traits\Get;

class Result
{
	use Get;

	protected $items = [];
	protected $success;

	public function __construct( $success, array $prop = [] )
	{
		$this->success = (bool) $success;
		$this->items = $prop;
	}

	public function success()
	{
		return $this->success;
	}
}
