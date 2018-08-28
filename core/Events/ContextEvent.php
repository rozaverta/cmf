<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace EApp\Events;

use EApp\Context\Context;
use EApp\Support\Collection;

/**
 * Class ContextEvent
 *
 * @property Context context
 * @property Collection collection
 *
 * @package EApp\Events
 */
class ContextEvent extends SystemEvent
{
	public function __construct( Context $context, Collection $collection )
	{
		parent::__construct(compact('context', 'collection'));
		$this->params_allowed[] = "context";
		$this->params_allowed_type["context"] = Context::class;
	}
}