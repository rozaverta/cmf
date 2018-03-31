<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\CI\Lang;
use EApp\Event\EventParamTrait;
use EApp\Event\Interfaces\EventInterface;

class LanguageEvent implements EventInterface
{
	use EventParamTrait;

	private $event_name;

	public function __construct( Lang $instance, $language, $event_name )
	{
		$this->params['instance'] = $instance;
		$this->params['language'] = $language;
		$this->event_name = $event_name;
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->event_name;
	}
}