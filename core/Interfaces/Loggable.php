<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.04.2018
 * Time: 19:09
 */

namespace EApp\Interfaces;

interface Loggable
{
	/**
	 * Add new log instance
	 *
	 * @param \EApp\Log $log
	 * @return mixed
	 */
	public function addLog( \EApp\Log $log );

	/**
	 * @param string | \EApp\Text $text
	 * @param int $code
	 * @return mixed
	 */
	public function addLogError( $text, $code = 0 );

	/**
	 * @param string | \EApp\Text $text
	 * @param int $code
	 * @return mixed
	 */
	public function addLogDebug( $text, $code = 0 );

	/**
	 * @return bool
	 */
	public function hasLogs();

	/**
	 * Get last log instance
	 *
	 * @param bool $clearReturn
	 * @return bool|\EApp\Log
	 */
	public function getLastLog( $clearReturn = false );

	/**
	 * Get all logs as array
	 *
	 * @param bool $clear
	 * @return mixed
	 */
	public function getLogs( $clear = false );

	/**
	 * Clean all logs
	 *
	 * @return mixed
	 */
	public function cleanLogs();

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function addCaptureLogListener(\Closure $capture);

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function removeCaptureLogListener(\Closure $capture);

	/**
	 * @param Loggable $transport
	 * @return $this
	 */
	public function addLogTransport( Loggable $transport );

	/**
	 * @return $this
	 */
	public function removeLogTransport();

	/**
	 * @param Loggable|null $transport
	 * @return mixed
	 */
	public function hasLogTransport( Loggable $transport = null );
}