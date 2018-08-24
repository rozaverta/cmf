<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

use EApp\View\Template;

/**
 * Class RenderCompleteEvent
 *
 * @property Template $template
 * @property string $body
 *
 * @package EApp\Events
 */
class RenderCompleteEvent extends SystemEvent
{
	public function __construct( Template $template, string $body )
	{
		parent::__construct(compact('template', 'body'));
		$this->params_allowed[] = "body";
	}
}