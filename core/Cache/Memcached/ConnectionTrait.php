<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 19:58
 */

namespace EApp\Cache\Memcached;

use EApp\App;
use EApp\Log;
use Memcached;

trait ConnectionTrait
{
	/**
	 * @var Memcached
	 */
	protected $connection;

	public function getConnection(): Memcached
	{
		return $this->connection;
	}

	protected function setConnection( Memcached $connection )
	{
		$this->connection = $connection;
	}

	protected function result( bool $result ): bool
	{
		if( $result )
		{
			return true;
		}

		$code = $this->connection->getResultCode();
		if( $code !== Memcached::RES_SUCCESS && $code !== Memcached::RES_NOTFOUND )
		{
			$message = $this->connection->getResultMessage();
			App::Log(
				new Log( "Memcache error. " . $message, "ERROR", $code )
			);
		}

		return false;
	}
}