<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.07.2018
 * Time: 1:31
 */

namespace EApp\System\Fs\Traits;

use EApp\Log;
use EApp\Support\Interfaces\Loggable;

trait UnlinkTrait
{
	protected function unlinkReal(\SplFileInfo $info, bool $throw = true)
	{
		if($info->isLink())
		{
			if( $this instanceof Loggable )
			{
				$this->log( new Log("Cache resource '{$info->getFilename()}' is link") );
			}

			if($throw)
			{
				throw new \InvalidArgumentException("Cache resource is link");
			}

			return false;
		}

		return $this->unlink($info, $throw);
	}

	protected function unlink(\SplFileInfo $info, bool $throw = true)
	{
		if( ! $info->isFile() )
		{
			if( $this instanceof Loggable )
			{
				$this->log( new Log("You can unlink only file") );
			}
			if($throw)
			{
				throw new \InvalidArgumentException("You can unlink only file");
			}
			return false;
		}

		if( @ unlink($info->getRealPath()) )
		{
			return true;
		}

		$message = "Remove file error '{$info->getFilename()}'";
		$error = error_get_last();
		if( isset($error["message"]) )
		{
			$message .= ": " . $error["message"];
		}

		if( $this instanceof Loggable )
		{
			$this->log( new Log($message) );
		}

		if( $throw )
		{
			throw new \Exception($message);
		}

		return false;
	}
}
