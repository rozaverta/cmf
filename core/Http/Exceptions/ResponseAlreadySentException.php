<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 14:08
 */

namespace EApp\Http\Exceptions;

use RuntimeException;

/**
 * ResponseAlreadySentException
 *
 * Exceptions used for when a response is attempted to be sent after its already been sent
 */
class ResponseAlreadySentException extends RuntimeException implements HttpExceptionInterface {}