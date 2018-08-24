<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2017
 * Time: 19:46
 */

namespace EApp\Database;

interface ConnectionResolverInterface
{
	/**
	 * GetTrait a database connection instance.
	 *
	 * @param  string  $name
	 * @return \EApp\Database\ConnectionInterface
	 */
	public function connection($name = null);

	/**
	 * GetTrait the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection();

	/**
	 * SetTrait the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name);
}