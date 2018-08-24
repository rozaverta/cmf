<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 21:11
 */

namespace EApp\Filesystem\Traits;


use EApp\App;
use EApp\Interfaces\Loggable;

trait GetErrorTrait
{
	protected function getError(\ErrorException $e): bool
	{
		$err = error_get_last();
		if( isset($err['message']) )
		{
			App::Log(
				new \Exception($err['message'], 0, $e)
			);
		}

		$error = $e->getMessage();
		if( $this instanceof Loggable )
		{
			$this->addLogError($error);
		}
		else
		{
			App::Log($e);
		}

		return false;
	}
}