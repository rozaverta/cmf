<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;

class ReadyEvent extends SystemEvent
{
	public function __construct( App $app, $cache = false )
	{
		parent::__construct( $app );
		$this->params['cache'] = $cache;
	}
}