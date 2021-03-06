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

// base constants

define("CORE_DIR", __DIR__ . DIRECTORY_SEPARATOR );

defined("ELS_CMS")          || define( "ELS_CMS"        , true );
defined("NOW_MICROTIME")    || define( "NOW_MICROTIME"  , microtime(true) );
defined("NOW_TIME")         || define( "NOW_TIME"       , time() );

// class alias

class_alias('EApp\\Database\\Manager', 'DB', true);

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

	function exc_cmd()
	{
		return defined("CONSOLE_MODE") ? CONSOLE_MODE : function_exists("php_sapi_name") && php_sapi_name() == 'cli';
	}

	function exc_print( $text, array $args = [])
	{
		if( exc_cmd() )
		{
			$text .= "\n";
		}
		else if( ! headers_sent() )
		{
			header("Content-Type: text/plain; charset=utf-8");
		}

		vprintf($text, $args);
		exit();
	}

	function exc_has_app()
	{
		return class_exists("\\EApp\\App", false);
	}

	function exc_db($exception)
	{
		if( exc_has_app() && $exception instanceof \EApp\Database\QueryException )
		{
			$app = \EApp\App::getInstance();
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
	}

	if( ! defined("SYSTEM_INSTALL") || ! SYSTEM_INSTALL )
	{
		exc_print("%s. %s, %s", [($is_error ? "Fatal error" : "System error"), $exception->getCode(), $exception->getMessage()] );
	}

	$app = exc_has_app() ? \EApp\App::getInstance() : false;

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

		exc_db($exception);

		// event

		try {
			\EApp\Event\EventManager::dispatch(
				new \EApp\Events\ThrowableEvent($exception),
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
		}
		catch(\EApp\Database\QueryException $e) {
			exc_db($e);
		}
		catch( \EApp\Exceptions\WriteException $e) {}

		try {
			$app->close();
		}
		catch(\EApp\Database\QueryException $e) {
			exc_db($e);
		}
		catch( \EApp\Exceptions\WriteException $e) {}
	}

	if( $output instanceof \Closure )
	{
		$output();
		$is_send = true;
	}

	if( $is_send )
	{
		exit;
	}

	if( exc_cmd() )
	{
		$response->setBody( sprintf( "\033[31;31m%s\033[0m", $title . ( $code ? " [{$code}]:" : ':' ) ) . " " . $message . PHP_EOL );
	}
	else
	{
		$page404 =  $exception instanceof \EApp\Exceptions\PageNotFoundException && $code == 404;
		$body = null;

		// 404 page from file
		if( $page404 )
		{
			$file = APP_DIR . '404_error.html';
			if( file_exists($file) )
				$body = @ file_get_contents($file);
		}

		if( $page404 )
		{
			$title = $message;
			$head_title = $code;
		}
		else
		{
			$head_title = $title;
			if( $code )
				$head_title .= " [{$code}]";
		}

		$mode = defined("DEBUG_MODE") ? DEBUG_MODE : "production";
		$debug = "";

		// default error page from file
		if( !$body )
		{
			$file = APP_DIR . 'system_error.inc.php';
			if( file_exists($file) )
			{
				$compact = compact('title', 'head_title', 'message', 'mode', 'is_error', 'replace');
				$compact['charset'] = 'utf-8';
				ob_start();
				\Eapp\Helper::includeFile($file, $compact);
				$body = ob_get_contents();
				ob_end_clean();
			}
			else if($mode === "development" && (! $exception instanceof \EApp\Exceptions\PageNotFoundException || $code !== 404)) {
				$debug = '<pre>' . get_class($exception) . ", trace: \n" . $exception->getTraceAsString() . '</pre>';
			}
		}

		if( ! $body )
			$body = <<<EOT
<!DOCTYPE html>
<html>
<head>
	<title>{$title}</title>
	<meta charset="utf-8" />
	<meta http-equiv="Content-Type" content="text-html; charset=utf-8" />
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300&subset=latin,cyrillic" rel="stylesheet" type="text/css" />
	<style type="text/css">html{background-color:#f3f3f3}body{font:300 14px Roboto,Verdana,Arial,sans-serif;margin:0;padding:0;color:#333}.center{background-color:white;margin:120px auto;position:relative;width:440px;padding-bottom:20px;border-radius:2px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.2);box-shadow:0 1px 3px rgba(0,0,0,0.2)}.center pre{margin:10px 20px;border:1px solid #ccc;padding:10px;overflow:auto}.center.mode-development{width:800px}h1{font-weight:400;font-size:22px;color:#000;padding:20px;margin:1px 1px 30px;border-bottom:1px solid #eee}p{margin:10px 20px}</style>
</head>
<body>
<div class="center mode-{$mode}">
	<h1>{$head_title}</h1>
	<p>{$message}</p>
	{$debug}
</div>
</body>
</html>
EOT;

		$response->setBody($body);
	}

	$send = false;

	try {
		$response->send(true);
		$send = true;
	}
	catch(\EApp\Database\QueryException $e) {
		exc_db($e);
	}
	catch( \EApp\Exceptions\WriteException $e) {}

	if( ! $send )
		exc_print((exc_cmd() ? "\033[31;31m%s (%s)\033[0m" : "%s (%s)") . ": %s", [$title, $code, $message]);

	exit;
});

// detect hosts

$host = \EApp\Host::getInstance();

if( $host->reload() )
{
	if( $host->getStatus() === "redirect" )
	{
		$host->isCmd() || exit;
	}
	else
	{
		$host->define();
	}
}
else if( ! $host->isCmd() )
{
	headers_sent() || header("Content-Type: text/plain; charset=utf-8");
	echo "The selected domain is not installed or the configuration file is not specified host ID";
	exit;
}

defined("CONSOLE_MODE")     || define( "CONSOLE_MODE"   , $host->isCmd() );
defined("BASE_ENCODING")    || define( "BASE_ENCODING"  , "UTF-8" );
defined("BASE_PROTOCOL")    || define( "BASE_PROTOCOL"  , CONSOLE_MODE || ! $host->isServerSsl() ? "http" : "https" );