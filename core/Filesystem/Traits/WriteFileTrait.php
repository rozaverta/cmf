<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:52
 */

namespace EApp\Filesystem\Traits;

use EApp\CI\PhpExport;
use EApp\Exceptions\WriteException;
use EApp\Filesystem\Exceptions\WriteFileException;
use EApp\Interfaces\Loggable;

trait WriteFileTrait
{
	use GetErrorTrait;

	/**
	 * Write data file from string
	 *
	 * @param string $file
	 * @param string $data
	 * @param bool $append
	 * @return bool
	 */
	protected function writeFile( string $file, string $data, bool $append = false )
	{
		return $this->write(
			$file, $data, $append
		);
	}

	/**
	 * Write data process from the iteration callback
	 *
	 * @param string $file
	 * @param \Closure $data
	 * @param bool $append
	 * @return bool
	 */
	protected function writeFileProcess( string $file, \Closure $data, bool $append = false )
	{
		return $this->write(
			$file, $data, $append
		);
	}

	/**
	 * Write data file as php value (export data)
	 *
	 * @param string $file
	 * @param \Closure $data
	 * @param string $data_name
	 * @return bool
	 */
	protected function writeFileExport( string $file, $data, string $data_name = 'data' )
	{
		return $this->write(
			$file,
			'<' . "?php defined('ELS_CMS') || exit('Not access'); \n" . PhpExport::getInstance()->data($data, $data_name, true, true),
			false
		);
	}

	/**
	 * Write data
	 *
	 * @param string $file
	 * @param $data
	 * @param $append
	 * @return bool
	 */
	private function write( string $file, $data, $append ): bool
	{
		if( function_exists('error_clear_last') )
		{
			error_clear_last();
		}

		// check directory exists or create empty directory for file
		$path = dirname($file);
		if( ! is_dir($path) )
		{
			try
			{
				if( ! @ mkdir($path, 0755, true) ) throw new WriteException("Cannot create the '{$path}' directory");
			}
			catch( \ErrorException $e )
			{
				return $this->getError($e);
			}
		}

		// check directory is writable
		if( ! is_writable($path) )
		{
			return $this->getError(new WriteFileException($file));
		}

		// detect write mode
		// ignore append flag if file is not exists
		$mode = "wa+";
		if( $append )
		{
			if( file_exists( $file ) )
			{
				$mode = "w";
			}
			else
			{
				$append = false;
			}
		}

		try {
			$handle = @ fopen( $file, $mode );
			if( ! $handle )
			{
				throw new WriteFileException($file,"Cannot open the '{$file}' file for write");
			}

			$callable = $data instanceof \Closure;
			if( flock( $handle, LOCK_EX ) )
			{
				if( $callable )
				{
					do
					{
						$content = $data( $append );
						if( ! is_string($content) || strlen($content) < 1 )
						{
							break;
						}
						if( fwrite( $handle, $content ) === false )
						{
							throw new WriteFileException($file);
						}
					}
					while(true);
				}
				else if( fwrite( $handle, $data ) === false )
				{
					throw new WriteFileException($file);
				}

				fflush( $handle );
				flock(  $handle, LOCK_UN );
			}
			else
			{
				throw new WriteFileException($file);
			}
		}
		catch( \ErrorException $e )
		{
			if( isset($handle) && ! $append && file_exists($file) )
			{
				@ unlink($file);
			}
			return $this->getError($e);
		}
		finally
		{
			if( isset($handle) )
			{
				fclose($handle);
			}
		}

		if( $this instanceof Loggable )
		{
			$this->addLogDebug("The '{$file}' file is successfully " . ($append ? "updated" : "created"));
		}

		return true;
	}
}