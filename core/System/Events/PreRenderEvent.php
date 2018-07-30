<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;

/**
 * Class PreRenderEvent
 *
 * @property \EApp\App $app
 * @property bool $cache
 * @property bool $cacheable
 *
 * @package EApp\System\Events
 */
class PreRenderEvent extends SystemEvent
{
	public function __construct( App $app, $cache = false, $cacheable = false )
	{
		parent::__construct( $app );
		$this->params['cache'] = $cache;
		$this->params['cacheable'] = ! $cache && $cacheable;
		$this->params_allowed[] = "cacheable";
	}
}