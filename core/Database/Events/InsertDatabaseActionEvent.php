<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2018
 * Time: 1:21
 */

namespace EApp\Database\Events;

use EApp\Database\Connection;

/**
 * Class InsertDatabaseActionEvent
 *
 * @property \EApp\SecurityFilter\Value[] $data
 *
 * @package EApp\Database\Events
 */
class InsertDatabaseActionEvent extends DatabaseActionEvent
{
	public function __construct( Connection $connection, string $table_name, array $data )
	{
		parent::__construct( $connection, $table_name, "insert", compact('data') );
	}
}