<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 20:49
 */

namespace EApp\Filesystem\Exceptions;

trait PathExceptionTrait
{
	private $path;

	public function getPath(): string
	{
		return $this->path;
	}

	protected function setPath(string $path)
	{
		$this->path = $path;
	}
}