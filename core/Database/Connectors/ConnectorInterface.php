<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 4:08
 */

namespace EApp\Database\Connectors;

interface ConnectorInterface
{
	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return \PDO
	 */
	public function connect(array $config);

	/**
	 * Select database.
	 *
	 * @param  string  $base
	 */
	public function select($base);

	/**
	 * Close connection.
	 */
	public function close();
}