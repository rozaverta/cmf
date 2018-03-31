<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 4:14
 */

namespace EApp\DB;

use EApp\Support\Str;
use Exception;

trait DetectsLostConnectionsTrait
{
	/**
	 * Determine if the given exception was caused by a lost connection.
	 *
	 * @param  \Exception $e
	 * @return bool
	 */
	protected function causedByLostConnection(Exception $e)
	{
		$message = $e->getMessage();
		return Str::contains($message, [
			'server has gone away',
			'no connection to the server',
			'Lost connection',
			'is dead or not enabled',
			'Error while sending',
			'decryption failed or bad record mac',
			'server closed the connection unexpectedly',
			'SSL connection has been closed unexpectedly',
			'Error writing data to the connection',
			'Resource deadlock avoided',
			'ConnectionEvent() on null',
			'child connection forced to terminate due to client_idle_limit',
		]);
	}
}