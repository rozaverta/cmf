<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.07.2018
 * Time: 1:31
 */

namespace EApp\System\Fs;

use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Fs\Traits\RelativePathTrait;
use EApp\System\Fs\Traits\UnlinkTrait;

class CacheInfo extends \SplFileInfo implements Loggable
{
	use UnlinkTrait;
	use RelativePathTrait;
	use LoggableTrait;

	public function __construct( $file_name )
	{
		$this->setRelativePath($file_name);
		if( !strlen($this->relative) )
		{
			throw new \InvalidArgumentException("Empty file");
		}

		parent::__construct( APP_DIR . "cache" . DIRECTORY_SEPARATOR . $this->relative );
	}

	public function fs(): CacheFs
	{
		if( $this->isFile() )
		{
			throw new \InvalidArgumentException("Cache resource is file");
		}

		return new CacheFs($this->relative);
	}

	public function clean( bool $throw = false )
	{
		if( $this->isFile() )
		{
			return $this->unlink( $this, $throw );
		}
		else
		{
			return $this->fs()->clean(true, $throw);
		}
	}
}
