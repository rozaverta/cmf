<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 19:40
 */

namespace EApp\Events;

use EApp\Language\Lang;
use EApp\Event\Event;

/**
 * Class LanguageEvent
 *
 * @property Lang $instance
 * @property string $language
 *
 * @package EApp\Events
 */
class LanguageLoadEvent extends Event
{
	public function __construct( Lang $instance )
	{
		parent::__construct("onLanguageLoad", [
			'instance' => $instance,
			'language' => $instance->getCurrent()
		]);
	}
}