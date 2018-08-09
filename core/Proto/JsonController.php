<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.09.2015
 * Time: 0:15
 */

namespace EApp\Proto;

use EApp\App;
use EApp\Database\QueryException;
use EApp\Event\EventManager;
use EApp\Prop;
use EApp\System\Interfaces\ControllerContentOutput;
use EApp\System\Events\ThrowableEvent;
use EApp\Component\Module as SystemModule;

abstract class JsonController extends Controller implements ControllerContentOutput
{
	public function __construct( SystemModule $module, array $prop = [] )
	{
		// json content can't be cacheable
		unset($prop["cacheable"]);

		parent::__construct($module, $prop);

		App::Response()->header("Content-Type", "application/json; charset=utf-8");
		EventManager::listen(
			"onSystemException",
			function( ThrowableEvent $event )
			{
				$code = $event->exception->getCode();
				if( ! $event->app->loadIs('Controller') || $event->app->Controller !== $this || in_array( $code, [403, 404, 500] ) )
				{
					return null;
				}

				// hide sql query
				$this->pageData =
					[
						"status" => "error",
						"message" => $event->exception instanceof QueryException ? 'DataBase fatal error' : $event->exception->getMessage()
					];

				if( $code )
				{
					$this->pageData['code'] = $code;
				}

				return function()
				{
					$this->output();
				};
			});
	}

	public function isRaw()
	{
		return true;
	}

	public function output()
	{
		if( is_object($this->pageData) )
		{
			$json = get_object_vars($this->pageData);
		}
		else if( !is_array( $this->pageData ) )
		{
			$json = ["response" => $this->pageData ];
		}
		else {
			$json = $this->pageData;
		}

		$this->pageData = [];

		if( !isset( $json["status"] ) )
		{
			$json["status"] = "ok";
		}

		if( Prop::cache('system')->get('debug') && !isset($json['debug']) )
		{
			$json['debug'] = [
				'time' => microtime(true) - NOW_MICROTIME
			];
		}

		App::Response()->json($json);
	}
}
