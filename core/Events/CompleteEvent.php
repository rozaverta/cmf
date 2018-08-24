<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:45
 */

namespace EApp\Events;

/**
 * Class CompleteEvent
 *
 * @property string $content_type
 * @property bool $cache
 *
 * @package EApp\Events
 */
class CompleteEvent extends SystemEvent
{
	public function __construct( string $content_type, bool $cache = false )
	{
		parent::__construct(compact('content_type', 'cache'));
	}
}