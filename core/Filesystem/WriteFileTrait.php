<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:52
 */

namespace EApp\Filesystem;

use EApp\App;
use EApp\CI\PhpExport;
use EApp\Filesystem\Exceptions\WriteException;
use EApp\Support\Interfaces\Loggable;

trait WriteFileTrait
{
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
				return $this->writeError($e, $file);
			}
		}

		// check directory is writable
		if( ! is_writable($path) )
		{
			return $this->writeError(new WriteException, $file);
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
				throw new WriteException("Cannot open the '{$file}' file for write");
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
							throw new WriteException;
						}
					}
					while(true);
				}
				else if( fwrite( $handle, $data ) === false )
				{
					throw new WriteException;
				}

				fflush( $handle );
				flock(  $handle, LOCK_UN );
			}
			else
			{
				throw new WriteException;
			}
		}
		catch( \ErrorException $e )
		{
			if( isset($handle) && ! $append && file_exists($file) )
			{
				@ unlink($file);
			}
			return $this->writeError($e, $file);
		}
		finally
		{
			if( isset($handle) )
			{
				fclose($handle);
			}
		}

		return $this->writeDebug($file, $append);
	}

	private function writeDebug( string $file, bool $append ): bool
	{
		if( $this instanceof Loggable )
		{
			$this->addLogDebug("The '{$file}' file is successfully " . ($append ? "updated" : "created"));
		}

		return true;
	}

	private function writeError( \ErrorException $e, $file ): bool
	{
		$error = $e->getMessage();

		if( $this instanceof Loggable )
		{
			if( ! $error )
			{
				$err = error_get_last();
				$error = $err['message'] ?? "Cannot write the '{$file}' file, unknown error";
			}

			$this->addLogError($error);
		}
		else if( $error )
		{
			App::Log($e);
		}
		else
		{
			$err = error_get_last();
			App::Log(
				new \Exception($err['message'] ?? "Cannot write the '{$file}' file, unknown error", 0, $e)
			);
		}

		return false;
	}
}