<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2017
 * Time: 5:22
 */

namespace EApp\DB\Events;

use EApp\Event\EventParamTrait;
use EApp\Event\Interfaces\EventInterface;

abstract class DataBaseEvent implements EventInterface
{
	use EventParamTrait;

	 /**
	  * Get event name
	  *
	  * @return string
	  */
	 public function getName()
	 {
		return 'onDataBase';
	 }
 }