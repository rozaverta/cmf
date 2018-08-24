<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.09.2015
 * Time: 0:15
 */

namespace EApp\Controllers;

use EApp\App;
use EApp\Database\QueryException;
use EApp\Debug;
use EApp\Event\EventManager;
use EApp\Prop;
use EApp\Controllers\Interfaces\ControllerContentOutput;
use EApp\Events\ThrowableEvent;
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
			"onThrowable",
			function( ThrowableEvent $event )
			{
				$code = $event->throwable->getCode();
				if( ! $event->app->loadIs('Controller') || $event->app->Controller !== $this || in_array( $code, [403, 404, 500] ) )
				{
					return null;
				}

				// hide sql query
				$this->page_data =
					[
						"status" => "error",
						"message" => $event->throwable instanceof QueryException ? 'DataBase fatal error' : $event->throwable->getMessage()
					];

				if( $code )
				{
					$this->page_data['code'] = $code;
				}

				return function()
				{
					defined("CONSOLE_MODE") && CONSOLE_MODE || $this->output();
				};
			});
	}

	public function isRaw()
	{
		return true;
	}

	public function output()
	{
		if( !isset( $this->page_data["status"] ) )
		{
			$this->page_data["status"] = "ok";
		}

		if( Prop::cache('system')->get('debug') && ! isset($this->page_data['debug']) )
		{
			$this->page_data['debug'] = Debug::toArray();
		}

		App::Response()->json($this->page_data);
	}
}