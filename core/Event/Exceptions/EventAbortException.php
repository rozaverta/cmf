<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.09.2017
 * Time: 5:47
 */

namespace EApp\Event\Exceptions;

use EApp\Event\Interfaces\EventExceptionInterface;

class EventAbortException extends \Exception implements EventExceptionInterface
{

}