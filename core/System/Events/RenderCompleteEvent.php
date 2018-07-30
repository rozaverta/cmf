<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\System\Events;

use EApp\App;
use EApp\Template\Template;

class RenderCompleteEvent extends SystemEvent
{
	public function __construct( App $app, Template $template, $body )
	{
		parent::__construct( $app );
		$this->params['template'] = $template;
		$this->params['body'] = $body;
		$this->params_allowed[] = "body";
	}
}