<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;

use EApp\Event\Event;
use EApp\Http\Response;

/**
 * Class ResponseRedirectEvent
 *
 *
 * @property Response response
 * @property string location
 * @property boolean permanent
 * @property boolean refresh
 *
 * @package EApp\Http\Events
 */
class ResponseRedirectEvent extends Event
{
	public function __construct( Response $response, string $location, bool $permanent, bool $refresh )
	{
		parent::__construct('onResponseRedirect', compact('response', 'location', 'permanent', 'refresh'));
	}
}