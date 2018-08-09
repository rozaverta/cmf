<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\Database\Events;

use EApp\Database\Connection;

class TransactionBeginningEvent extends TransactionEvent {
	public function __construct( Connection $connection )
	{
		parent::__construct( $connection, "begin" );
	}
}