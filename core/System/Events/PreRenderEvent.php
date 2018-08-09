<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

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
	public function __construct( bool $cache = false, bool $cacheable = false )
	{
		parent::__construct([
			'cache' => $cache,
			'cacheable' => ! $cache && $cacheable
		]);
		$this->params_allowed[] = "cacheable";
	}
}