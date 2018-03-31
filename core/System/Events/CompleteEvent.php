<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:45
 */

namespace EApp\System\Events;

use EApp\App;

/**
 * Class CompleteEvent
 *
 * @property \EApp\App $app
 * @property string $content_type
 *
 * @package EApp\System\Events
 */
class CompleteEvent extends SystemEvent
{
	public function __construct( App $app, $content_type, $cache = false )
	{
		parent::__construct( $app );
		$this->params['content_type'] = $content_type;
		$this->params['cache'] = (bool) $cache;
	}
}
