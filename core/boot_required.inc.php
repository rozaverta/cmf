<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2017
 * Time: 13:50
 */

if( ! defined("BASE_DIR") ) {
	throw new \Exception("BASE_DIR is not defined");
}

if( ! defined("ELS_CMS") ) {
	define("ELS_CMS", true);
}

// base constants

defined("NOW_MICROTIME")    || define( "NOW_MICROTIME"  , microtime(true) );
defined("NOW_TIME")         || define( "NOW_TIME"       , time() );
defined("BASE_ENCODING")    || define( "BASE_ENCODING"  , "UTF-8" );
defined("BASE_PROTOCOL")    || define( "BASE_PROTOCOL"  , isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http' );
defined("CONSOLE_MODE")     || define( "CONSOLE_MODE"   , function_exists("php_sapi_name") && php_sapi_name() == 'cli' );

define("CORE_DIR", __DIR__ . DIRECTORY_SEPARATOR );

// detect hosts and load or create application constants

$file = BASE_DIR . "host.php";
if( file_exists($file) )
{
	include $file;
	if( ! defined("APP_HOST") )
	{
		$message = "The selected domain is not installed or the configuration file is not specified host ID";
		if( CONSOLE_MODE )
		{
			$message .= "\n";
		}
		else if( ! headers_sent() )
		{
			header("Content-Type: text/plain; charset=utf-8");
		}

		echo $message;
		exit();
	}
}
else
{
	define( "APP_HOST"    , ( empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'] ) );
	define( "APP_DIR"     , BASE_DIR . "application" . DIRECTORY_SEPARATOR );
	define( "ASSETS_DIR"  , BASE_DIR . "assets" . DIRECTORY_SEPARATOR );
	define( "ASSETS_PATH" , "/assets/" );
}

// class alias

class_alias('EApp\\DB\\Manager', 'DB', true);

// Обработка ошибок

set_exception_handler(static function( $exception )
{
	// fix recursive
	static $run = false;
	if( $run )
	{
		return;
	}

	$run = true;
	$is_error = false;

	if( PHP_VERSION >= 7 && $exception instanceof \Error )
	{
		$is_error = true;
	}

	// if there is something there clear the data buffer

	if( function_exists('ob_get_level') )
	{
		$ob_level = ob_get_level() ;
		while( $ob_level -- > 0 )
		{
			ob_end_clean();
		}
	}

	if( ! defined("SYSTEM_INSTALL") || ! SYSTEM_INSTALL )
	{
		printf("%s. %s, %s", ($is_error ? "Fatal error" : "System error"), $exception->getCode(), $exception->getMessage());
		exit();
	}

	// todo add database connect test

	$app = class_exists("\\EApp\\App", false) ? \EApp\App::getInstance() : false;

	$title = $is_error ? "Fatal error" : "System error";
	$code = $exception->getCode();
	$message = $exception->getMessage();
	$output = false;
	$is_send = false;
	$response = $app ? $app->Response : new \EApp\Http\Response();

	// header code

	if( ! $is_send )
	{
		$response
			->header("Content-Type", "text/html; charset=utf-8")
			->setBody('');

		if( $code >= 200 && $code <= 505 )
		{
			$response->setCode($code);
		}
	}

	if( $app )
	{
		$is_send = $response->isSent() || $response->isLocked();

		// write an error to the log file
		$app->Log->exception($exception);

		// database error
		if( $exception instanceof \EApp\DB\QueryException )
		{
			$app->Log->line("Error sql query: " . $exception->getSql());
			$bindings = $exception->getBindings();
			if( count($bindings) )
			{
				$app->Log->line("Sql binding: " . implode(", ", $bindings));
			}

			$conn = \DB::connection();
			if( $conn->transactionLevel() > 0 )
			{
				$conn->rollBack();
			}
		}

		// event
		\EApp\Event\EventManager::dispatch(
			'onSystemException',
			new \EApp\System\Events\ExceptionEvent( $app, $exception ),
			function( $result ) use ( & $output ) {
				if( $result instanceof \Closure )
				{
					$output = $result;
					return false;
				}
				else
				{
					return null;
				}
			});

		$app->close();
	}

	if( $output instanceof \Closure )
	{
		$output();
		$is_send = true;
	}

	if( $is_send )
	{
		exit();
	}

	if( CONSOLE_MODE )
	{
		$response->setBody( sprintf( "\033[31;31m%s\033[0m", $title . ( $code ? " [{$code}]:" : ':' ) ) . " " . $message . PHP_EOL );
	}
	else
	{
		$page404 =  $exception instanceof \EApp\Support\Exceptions\PageNotFoundException && $code == 404;
		$body = null;

		// 404 page from file
		if( $page404 )
		{
			$file = APP_DIR . '404_error.html';
			if( file_exists($file) )
			{
				$body = @ file_get_contents($file);
			}
		}

		// default error page from file
		if( !$body )
		{
			$file = APP_DIR . 'system_error.html';
			if( file_exists($file) )
			{
				$body = @ file_get_contents($file);
			}
		}

		if( $page404 )
		{
			$title = $message;
			$head_title = $code;
		}
		else
		{
			$head_title = $title . ( $code ? " [{$code}]" : '' );
		}

		if( ! $body )
			$body = '
<!DOCTYPE html>
<html>
<head>
	<title>{{ $title }}</title>
	<meta charset="utf-8" />
	<meta http-equiv="Content-Type" content="text-html; charset=utf-8" />

	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300&subset=latin,cyrillic" rel="stylesheet" type="text/css" />
	<style type="text/css">
		html { 
			background-color: #f3f3f3; 
		}
		body { 
			font: 300 14px Roboto, Verdana, Arial, sans-serif;
			margin: 0;
			padding: 0;
			color: #333;
		}
		.center {
			background-color: white;
			margin: 120px auto;
			position: relative;
			width: 440px;
			padding-bottom: 20px;
			border-radius: 2px;
			-webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.2);
			box-shadow: 0 1px 3px rgba(0,0,0,0.2);
		}
		h1 { font-weight: 400; font-size: 22px; color: #000; padding: 20px; margin: 1px 1px 30px; border-bottom: 1px solid #eee; }
		p { margin: 10px 20px; }
	</style>
</head>
<body>
<div class="center">
	<h1>{{ $head_title }}</h1>
	<p>{{ $message }}</p>
</div>
</body>
</html>';

		$replace = compact('title', 'head_title', 'message');
		$replace['charset'] = 'utf-8';

		$response->setBody(
			preg_replace_callback(
				'/\{\{\s*\$([a-zA-Z_]+)\s*\}\}/',
				function($m) use ($replace)
				{
					$name = strtolower(trim($m[1]));
					return isset($replace[$name]) ? $replace[$name] : '';
				},
				$body
			)
		);
	}

	$response->send(true);
	exit();
});
