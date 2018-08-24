<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

use EApp\Language\Lang;
use EApp\Event\Event;

/**
 * Class LanguageEvent
 *
 * @property \EApp\Language\Lang $instance
 * @property string $language
 *
 * @package EApp\Events
 */
class LanguageEvent extends Event
{
	public function __construct( Lang $instance )
	{
		parent::__construct("onLanguage", [
			'instance' => $instance,
			'language' => $instance->getCurrent()
		]);

		$this->params_allowed[] = "language";
		$this->params_allowed_type["language"] = static function( $language ) {
			return is_string($language) && Lang::valid($language);
		};
	}
}