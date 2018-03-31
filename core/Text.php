<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:45
 */

namespace EApp;

class Text
{
	/**
	 * Text line
	 *
	 * @var string
	 */
	public $text;

	/**
	 * Replacement
	 *
	 * @var array|bool
	 */
	public $replace = false;

	public function __construct( $text, ... $args )
	{
		$this->text = (string) $text;
		if( $num = count($args) > 0 )
		{
			$this->text = preg_replace('/(?!\')%([sd])/', '\'%$1\'', $this->text);
			$this->replace = $num == 1 && is_array($args[0]) ? $args[0] : $args;
		}
	}

	public function __toString()
	{
		return $this->replace === false ? $this->text : vsprintf($this->text, $this->replace);
	}
}