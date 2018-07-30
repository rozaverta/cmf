<?php

namespace EApp\DB\Query;

use EApp\DB\Manager;

class ExpressionWrap extends Expression
{
	/**
	 * The replace array of the expression.
	 *
	 * @var mixed
	 */
	protected $replace = [];

	/**
	 * Create a new raw query expression.
	 *
	 * @param  mixed $value
	 * @param array $replace
	 */
	public function __construct($value, $replace = [])
	{
		parent::__construct($value);
		$this->replace = $replace;
	}

	/**
	 * Get the value of the expression.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		$grammar = Manager::connection()->getQueryGrammar();
		return vsprintf( $this->value, array_map(function($val) use($grammar) {
			return $grammar->wrap($val);
		}, $this->replace) );
	}
}