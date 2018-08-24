<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 20:45
 */

namespace EApp\Filesystem\Exceptions;

trait FileExceptionTrait
{
	use PathExceptionTrait;

	private $pathname;

	public function getPathname(): string
	{
		return $this->pathname;
	}

	protected function setPathname( string $pathname)
	{
		$this->pathname = $pathname;
		$this->setPath(dirname($pathname));
	}
}