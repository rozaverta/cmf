<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.07.2018
 * Time: 1:31
 */

namespace EApp\System\Fs\Traits;

trait RelativePathTrait
{
	protected $relative = "";

	public function getRelativePath(): string
	{
		return $this->relative;
	}

	protected function setRelativePath(string $relative)
	{
		$relative = ltrim($relative, ".");
		$relative = trim($relative, "/");
		if( DIRECTORY_SEPARATOR !== "/" && strlen($relative) )
		{
			$relative = str_replace("/", DIRECTORY_SEPARATOR, $relative);
		}
		$this->relative = $relative;
	}
}
