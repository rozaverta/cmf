<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:25
 */

namespace EApp\CI;

use EApp\Prop;
use EApp\Support\Traits\SingletonInstance;
use EApp\Text;

/**
 * Class Log
 * @package CI
 * @method static Log getInstance()
 */
final class Log
{
	use SingletonInstance;

	private $logs = [];
	private $writeLevel = 0;
	private $levels =
		[
			"ALL"   => 0,
			"INFO"  => 1,
			"DEBUG" => 2,
			"ERROR" => 3
		];

	public function lastPhp()
	{
		$err = error_get_last();
		if( !isset($err['message']))
		{
			return false;
		}

		$text  = 'PHP Error: ' . $err['message'];
		$text .= ', file: ' . $err['file'];
		$text .= ', line: ' . $err['line'];

		return $this->line($text);
	}

	public function line( $text, $level = "ERROR", $code = 0 )
	{
		return $this->log( new \EApp\Log( $text, $level, $code ) );
	}

	public function log( \EApp\Log $log )
	{
		static $init = false;

		if( ! $init )
		{
			$init = true;
			$level = Prop::cache("system")->getOr("debug_level", "ALL");
			if( isset($this->levels[$level]) )
			{
				$this->writeLevel = $this->levels[$level];
			}
		}

		if( $this->writeLevel === 0 || isset($this->levels[$log->level]) && $this->levels[$log->level] >= $this->writeLevel )
		{
			$this->logs[] = (string) $log->translateOff()->line();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param \Exception | \Throwable $e
	 * @return bool
	 * @throws \Exception
	 */
	public function exception( $e )
	{
		$valid = PHP_VERSION < 7 ? ($e instanceof \Exception) : ($e instanceof \Throwable);
		if( !$valid )
		{
			trigger_error("Uncaught TypeError: Argument 1 passed to " . __CLASS__ . "::exception() must be an instance of \\Exceptions or \\Error for PHP_VERSION > 7", E_USER_ERROR);
		}

		$text  = 'exception error: ' . $e->getMessage();
		$text .= ', file: ' . $e->getFile();
		$text .= ', line: ' . $e->getLine();
		$text .= ', code: ' . $e->getCode();

		return $this->line( $text );
	}

	public function flush( $length = 0 )
	{
		$cnt = count( $this->logs );
		if( $cnt && $cnt >= $length )
		{
			$file = APP_DIR . "logs";
			if( !file_exists( $file ) )
			{
				@ mkdir( $file, 0777, true );
			}

			$file .= DIRECTORY_SEPARATOR . "log-" . date( "Y.m.d" ) . ".php";
			$new = !file_exists($file);

			if( ($fo = @ fopen( $file, "a+" )) && @ flock( $fo, LOCK_EX ) )
			{
				flock( $fo, LOCK_UN );
				$new && fwrite( $fo, '<' . '?php if( ! defined("ELS_CMS") ) exit("Not access"); ?' . ">\n" );

				foreach( $this->logs as $log )
				{
					fwrite( $fo, $log . "\n" );
				}

				fflush( $fo );
				flock( $fo, LOCK_UN );
				@ fclose( $fo );
			}

			$this->logs = [];
		}
	}

	public function __invoke( ...$args )
	{
		if( !count($args) )
		{
			return $this->lastPhp();
		}
		else
		{
			$first = $args[0];
			if( is_string($first) || $first instanceof Text )
			{
				return $this->line(...$args);
			}

			if( is_object($first) )
			{
				if( $first instanceof Log )
				{
					return $this->log($first);
				}

				if( PHP_VERSION < 7 ? ($first instanceof \Exception) : ($first instanceof \Throwable) )
				{
					return $this->exception($first);
				}

				if( method_exists($first, '__toString') )
				{
					$args[0] = (string) $first;
					return $this->line(...$args);
				}
			}
		}

		throw new \InvalidArgumentException('Invalid parameters of the log.');
	}

	public function __destruct()
	{
		$this->flush();
	}
}