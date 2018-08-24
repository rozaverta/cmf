<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 20:22
 */

namespace EApp\Filesystem\Traits;

use EApp\Filesystem\Exceptions\AccessFileException;
use EApp\Interfaces\Loggable;

trait DeleteFileTrait
{
	use GetErrorTrait;

	protected function deleteFile( string $file ): bool
	{
		if( ! file_exists($file) )
		{
			return true;
		}

		if( ! is_file($file) )
		{
			return $this->getError(
				new \ErrorException("The '{$file}' path is not file")
			);
		}

		try {
			if( ! @ unlink($file) )
			{
				throw new AccessFileException($file, "Cannot delete the '{$file}' file");
			}
		}
		catch(\ErrorException $e ) {
			return $this->getError($e);
		}

		if( $this instanceof Loggable )
		{
			$this->addLogDebug("The '{$file}' file was successfully deleted");
		}

		return true;
	}
}