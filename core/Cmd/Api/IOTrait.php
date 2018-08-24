<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 12:23
 */

namespace EApp\Cmd\Api;


use EApp\Cmd\IO\InputOutputInterface;

trait IOTrait
{
	/**
	 * @var InputOutputInterface | null
	 */
	private $IO = null;

	protected function setIO($IO)
	{
		$this->IO = $IO;
	}

	/**
	 * @return InputOutputInterface
	 */
	protected function getIO(): InputOutputInterface
	{
		return $this->IO;
	}

	/**
	 * @return bool
	 */
	protected function hasIO(): bool
	{
		return ! is_null($this->IO);
	}
}