<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 19:46
 */

namespace EApp\DB;

interface ConnectionResolverInterface
{
	/**
	 * Get a database connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\DB\ConnectionInterface
	 */
	public function connection($name = null);

	/**
	 * Get the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection();

	/**
	 * Set the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name);
}