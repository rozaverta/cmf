<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace EApp\Http\Events;

use EApp\Http\Response;

/**
 * Class ResponseFileEvent
 *
 * @property string $file       The path of the file to send
 * @property string $filename   The file's name
 * @property string $mime_type  The MIME type of the file
 *
 * @package EApp\Http\Events
 */
class ResponseFileEvent extends ResponseSendEvent
{
	public function __construct( Response $response, $file, $filename, $mime_type )
	{
		parent::__construct($response);
		$this->params['file'] = $file;
		$this->params['filename'] = $filename;
		$this->params['mime_type'] = $mime_type;
	}
}