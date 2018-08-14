<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 15:31
 */

namespace EApp\System\ConsoleCommands\IO;


class Option
{
	protected $answer;

	protected $value;

	public function __construct( string $answer, $value = null )
	{
		$this->answer = $answer;
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getAnswer(): string
	{
		return $this->answer;
	}

	/**
	 * @return null
	 */
	public function getValue()
	{
		return $this->value;
	}
}