<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 3:11
 */

namespace EApp\Support\Traits;

use EApp\App;

trait Write
{
	protected function _write( $file, $data, $attach = false )
	{
		$php = preg_match('/\.[a-z0-9]+$/', $file, $m) && strtolower($m[0]) === strtolower('.php');
		$callable = false;

		if( ! is_string( $data ) )
		{
			if( $data instanceof \Closure )
			{
				$callable = true;
			}
			else if( $php )
			{
				$data = "\n" . App::Php()->data( $data );
			}
			else
			{
				App::Log()->line(["Content data must be string for write to file '%s'", $file]);
				return false;
			}
		}

		$mode = "wa+";
		if( $attach )
		{
			if( file_exists( $file ) )
			{
				$mode = "w";
			}
			else
			{
				$attach = false;
			}
		}

		$get = false;
		if( function_exists('error_clear_last') )
		{
			error_clear_last();
		}

		if( $fo = @ fopen( $file, $mode ) )
		{
			if( @ flock( $fo, LOCK_EX ) )
			{
				flock(  $fo, LOCK_UN );
				if( ! $attach && $php )
				{
					fwrite( $fo, '<' . '?php if( ! defined("ELS_CMS") ) exit("Not access");' );
				}

				if( $callable )
				{
					do
					{
						$content = $data();
						if( is_string($content) && strlen($content) )
						{
							fwrite( $fo, $content );
						}
						else
						{
							break;
						}
					}
					while(true);
				}
				else
				{
					fwrite( $fo, $data );
				}

				fflush( $fo );
				flock(  $fo, LOCK_UN );
				$get = true;
			}

			@ fclose( $fo );
		}

		if( !$get )
		{
			App::Log()->lastPhp();
		}

		unset( $data );
		return $get;
	}

	protected function _makeDir( $dir )
	{
		$dir = rtrim( $dir, DIRECTORY_SEPARATOR );
		if( ! file_exists( $dir ) ) {
			@ mkdir( $dir, 0777, true );
		}
	}
}