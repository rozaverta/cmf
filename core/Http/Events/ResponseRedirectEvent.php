<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;


use EApp\Event\Interfaces\EventInterface;
use EApp\Event\EventParamTrait;
use EApp\Http\Response;

/**
 * Class ResponseRedirectEvent
 *
 *
 * @property Response response
 * @property string url
 * @property boolean permanent
 * @property boolean refresh
 *
 */
class ResponseRedirectEvent implements EventInterface
{
	use EventParamTrait;

	public function __construct( Response $response, $url, $permanent, $refresh )
	{
		$this->params = compact( 'response', 'url', 'permanent', 'refresh' );
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'onResponseRedirect';
	}
}