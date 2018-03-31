<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 16:01
 */

namespace EApp\Support\Traits;

use EApp\Log;

trait Logs
{
	private $_logs = [];

	public function hasLogs()
	{
		return count( $this->_logs ) > 0;
	}

	/**
	 * @param bool $clearReturn
	 * @return bool|\EApp\Log
	 */
	public function getLastLog( $clearReturn = false )
	{
		$count = count( $this->_logs );
		if( !$count )
		{
			return false;
		}

		if( $clearReturn )
		{
			return array_pop( $this->_logs );
		}
		else
		{
			return $this->_logs[$count-1];
		}
	}

	public function getLogs( $clear = false )
	{
		if( $clear )
		{
			if( count( $this->_logs ) )
			{
				$logs = $this->_logs;
				$this->_logClean();
				return $logs;
			}
			else
			{
				return [];
			}
		}
		else
		{
			return $this->_logs;
		}
	}

	protected function _logText( $text, $level = "ERROR", $code = 0 )
	{
		$log = new Log( $text, $level, $code );
		$this->_logs[] = $log;
		return ! ( $log->level === "ERROR" );
	}

	protected function _log( Log $log )
	{
		$this->_logs[] = $log;
		return ! ( $log->level === "ERROR" );
	}

	protected function _logClean()
	{
		$this->_logs = [];
	}

	/**
	 * @param Logs $logs
	 */
	protected function _logMergeLogs( $logs )
	{
		if( is_object($logs) )
		{
			$reflect = new \ReflectionClass( $logs );
			if( in_array( 'Traits\\Logs', $reflect->getTraitNames() ) )
			{
				foreach( $logs->getLogs(true) as $log )
				{
					$this->_logs[] = $log;
				}
			}
		}
	}
}