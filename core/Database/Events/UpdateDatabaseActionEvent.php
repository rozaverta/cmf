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
 * Class UpdateDatabaseActionEvent
 *
 * @property \EApp\SecurityFilter\Value[] $data
 * @property array $where
 *
 * @package EApp\Database\Events
 */
class UpdateDatabaseActionEvent extends DatabaseActionEvent
{
	public function __construct( Connection $connection, string $table_name, array $data, array $where )
	{
		parent::__construct( $connection, $table_name, "update", compact('data', 'where') );
	}
}