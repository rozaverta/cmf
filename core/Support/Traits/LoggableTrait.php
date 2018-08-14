<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 16:01
 */

namespace EApp\Support\Traits;

use EApp\InvokeCounter;
use EApp\Log;
use EApp\Support\Interfaces\Loggable;

trait LoggableTrait
{
	private $logs = [];

	private $logsCaptureCount = 0;

	private $logsCaptureCallbacks = [];

	private $logsIsTransport = false;

	/**
	 * @var null|\EApp\Support\Interfaces\Loggable
	 */
	private $logsTransport = null;

	public function hasLogs()
	{
		if($this->logsIsTransport)
		{
			return $this->logsTransport->hasLogs();
		}
		else
		{
			return count( $this->logs ) > 0;
		}
	}

	/**
	 * @param bool $clearReturn
	 * @return bool|\EApp\Log
	 */
	public function getLastLog( $clearReturn = false )
	{
		if($this->logsIsTransport)
		{
			return $this->logsTransport->getLastLog( $clearReturn );
		}

		$count = count( $this->logs );
		if( !$count )
		{
			return false;
		}

		if( $clearReturn )
		{
			return array_pop( $this->logs );
		}
		else
		{
			return $this->logs[$count-1];
		}
	}

	public function getLogs( $clear = false )
	{
		if($this->logsIsTransport)
		{
			return $this->logsTransport->getLogs( $clear );
		}

		if( $clear )
		{
			if( count( $this->logs ) )
			{
				$logs = $this->logs;
				$this->cleanLogs();
				return $logs;
			}
			else
			{
				return [];
			}
		}
		else
		{
			return $this->logs;
		}
	}

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function addCaptureLogListener(\Closure $capture)
	{
		if($this->logsIsTransport)
		{
			$this->logsTransport->addCaptureLogListener($capture);
		}
		else
		{
			$index = array_search($capture, $this->logsCaptureCallbacks, true);
			if($index === false)
			{
				$this->logsCaptureCallbacks[] = $capture;
				$this->logsCaptureCount ++;
			}
		}

		return $this;
	}

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function removeCaptureLogListener(\Closure $capture)
	{
		if($this->logsIsTransport)
		{
			$this->logsTransport->removeCaptureLogListener($capture);
		}
		else
		{
			$index = array_search($capture, $this->logsCaptureCallbacks, true);
			if($index !== false)
			{
				array_splice($this->logsCaptureCallbacks, $index, 1);
				$this->logsCaptureCount --;
			}
		}

		return $this;
	}

	/**
	 * @param $transport
	 * @return $this
	 */
	public function addLogTransport( Loggable $transport )
	{
		if( $this->logsIsTransport )
		{
			if($this->logsTransport !== $transport)
			{
				throw new \RuntimeException("This class already uses a different transport");
			}
		}

		else if( ! $transport->hasLogTransport($this) )
		{
			$this->logsIsTransport = true;
			$this->logsTransport = $transport;

			// move logs
			if(count($this->logs))
			{
				foreach($this->logs as $log)
				{
					$transport->addLog($log);
				}
				$this->logs = [];
			}

			// move capture
			if($this->logsCaptureCount > 0)
			{
				for($i = 0; $i < $this->logsCaptureCount; $i++)
				{
					$transport->addCaptureLogListener($this->logsCaptureCallbacks[$i]);
				}
				$this->logsCaptureCallbacks = [];
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function removeLogTransport()
	{
		if($this->logsIsTransport)
		{
			$this->logsIsTransport = false;
			$this->logsTransport = null;
		}
		return $this;
	}

	public function hasLogTransport( Loggable $transport = null )
	{
		if( is_null($transport) )
		{
			return $this->logsIsTransport;
		}
		else
		{
			return $transport === $this || $this->logsIsTransport && $this->logsTransport->hasLogTransport( $transport );
		}
	}

	/**
	 * @param Log $log
	 * @return $this
	 */
	public function addLog( Log $log )
	{
		// use transporter
		if($this->logsIsTransport)
		{
			$this->logsTransport->addLog($log);
		}

		// capture log
		else if($this->logsCaptureCount > 0)
		{
			$counter = new InvokeCounter(true);
			$native = $counter->getClosure();

			for($i = 0; $i < $this->logsCaptureCount; $i++)
			{
				call_user_func($this->logsCaptureCallbacks[$i], $log, $native);
				$counter->unfreeze();
			}

			if($counter->getCount() === $this->logsCaptureCount)
			{
				$this->logs[] = $log;
			}
		}

		// add default log
		else
		{
			$this->logs[] = $log;
		}

		return $this;
	}

	/**
	 * @param string | \EApp\Text $text
	 * @param int $code
	 * @return mixed
	 */
	public function addLogError( $text, $code = 0 )
	{
		return $this->addLog( new Log( $text, "ERROR", $code ) );
	}

	/**
	 * @param string | \EApp\Text $text
	 * @param int $code
	 * @return mixed
	 */
	public function addLogDebug( $text, $code = 0 )
	{
		return $this->addLog( new Log( $text, "DEBUG", $code ) );
	}

	public function cleanLogs()
	{
		// use transporter
		if($this->logsIsTransport)
		{
			$this->logsTransport->cleanLogs();
		}

		// add default log
		else
		{
			$this->logs = [];
		}

		return $this;
	}

	/**
	 * @param Loggable $logs
	 * @return $this
	 */
	public function logMergeLogs( Loggable $logs )
	{
		foreach( $logs->getLogs(true) as $log )
		{
			$this->logs[] = $log;
		}
		return $this;
	}
}