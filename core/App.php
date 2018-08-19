<?php

namespace EApp;

use EApp\CI\Log as CiLog;
use EApp\Component\Context;
use EApp\Component\MountPoint;
use EApp\Component\QueryContext;
use EApp\Component\Scheme\ContextSchemeDesigner;
use EApp\Component\Scheme\RouteSchemeDesigner;
use EApp\Database\Manager as DataBaseManager;
use EApp\Event\Event;
use EApp\Event\EventManager;
use EApp\Filesystem\Filesystem;
use EApp\Http\Request;
use EApp\Http\Response;
use EApp\Proto\Controller;
use EApp\Proto\Router;
use EApp\Support\Collection;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Exceptions\PageNotFoundException;
use EApp\System\Events\ContextEvent;
use EApp\System\Events\LanguageEvent;
use EApp\System\Events\SingletonEvent;
use EApp\System\Interfaces\ControllerContentOutput;
use EApp\Support\Interfaces\SingletonCompletable;
use EApp\Support\Traits\SingletonInstance;
use EApp\System\Events\BootEvent;
use EApp\System\Events\CompleteEvent;
use EApp\System\Events\LoadEvent;
use EApp\System\Events\PreRenderEvent;
use EApp\System\Events\ReadyEvent;
use EApp\System\Events\ShutdownEvent;
use EApp\Component\Module;
use EApp\Component\QueryRoutes;
use EApp\System\Terminal;
use EApp\View\PageCache;
use EApp\View\View;

/**
 * Elastic Content Management Framework
 *
 * @author GoshaV [Maniako] <gosha@rozaverta.com>
 * @date 24.08.2017 00:04
 *
 * Class Els
 *
 * @property \EApp\CI\Log Log
 * @property \EApp\CI\PhpExport $PhpExport
 * @property \EApp\CI\Uri Uri
 * @property \EApp\View\View View
 * @property \EApp\Filesystem\Filesystem Filesystem
 * @property \EApp\CI\Lang Lang
 * @property \EApp\CI\Session Session
 * @property \EApp\Database\Manager Database
 * @property Controller Controller
 * @property Context Context
 *
 * @property \EApp\Http\Response Response
 * @property \EApp\Http\Request Request
 *
 * @method static \EApp\CI\Log Log(...$args)
 * @method static \EApp\CI\PhpExport PhpExport()
 * @method static \EApp\CI\Uri Uri()
 * @method static \EApp\View\View View()
 * @method static \EApp\Filesystem\Filesystem Filesystem()
 * @method static \EApp\CI\Lang Lang(...$args)
 * @method static \EApp\CI\Session Session(...$args)
 * @method static \EApp\Database\Manager Database()
 * @method static Context Context()
 *
 * @method static \EApp\Http\Response Response()
 * @method static \EApp\Http\Request Request()
 *
 * @method static App getInstance()
 */
final class App
{
	use SingletonInstance;

	const VER = "0.0.1";

	const REDIRECT_HEADER  = 1;
	const REDIRECT_META    = 2;
	const REDIRECT_REFRESH = 3;

	private $ci = [];

	/**
	 * Create new Query builder
	 *
	 * @param string $table Table name
	 * @return Database\Query\Builder
	 */
	public function db( $table )
	{
		return DataBaseManager::table($table);
	}

	public function run()
	{
		static $is_run = false;
		static $result_type = 'html';

		if( $is_run )
		{
			return $result_type;
		}

		$is_run = true;

		// system shutdown

		register_shutdown_function(function() {
			$this->close();
		});

		// load system config
		// check install

		$sys = Prop::cache("system");

		define("SYSTEM_INSTALL", $sys->equiv("install", true) );

		if( $sys->equiv("status", "update") )
		{
			throw new \Exception("The website is temporarily unavailable");
		}

		// debugging

		if( !defined("DEBUG_MODE") )
		{
			define("DEBUG_MODE", $sys->get("debug") === false ? "off" : "on" );
		}

		if( $sys->get("debug") !== false && DEBUG_MODE !== "production" )
		{
			@ ini_set( "display_errors", "on" );
			error_reporting( E_ALL );
			if( $sys->equiv("debug", "html") )
			{
				ini_set('html_errors', 'on');
			}
		}

		// php init values

		if( $sys->isArray("ini_set") )
		{
			foreach($sys->get("ini_set") as $name => $value )
			{
				ini_set($name, $value);
			}
		}

		// run boot config

		foreach( Prop::file('boot') as $file )
		{
			Helper::includeFile($file, ['app' => $this]);
		}

		EventManager::dispatch(new BootEvent());

		// run
		// console mode

		if( defined("CONSOLE_MODE") && CONSOLE_MODE )
		{
			if( ! class_exists("Symfony\\Component\\Console\\Application", true) )
			{
				throw new \Exception("Symfony console package is not installed");
			}

			$term = new Terminal();
			$term->loadDefault();
			$term->run();
			return $result_type = 'cli';
		}

		// web access

		if( ! SYSTEM_INSTALL )
		{
			throw new \Exception("System is not install for this domain");
		}

		// load system module for check the current system version

		Module::cache(0);

		// load manifest data

		$uri = $this->Uri;
		$mnf = new Prop('manifest');
		$response = $this->Response;

		if( $mnf->count() && $uri->mode() == 'rewrite' && $uri->length > 0 && $mnf->getIs($uri->path) )
		{
			$key  = $uri->path;
			$data = Helper::value( $mnf->get($key) );

			if( is_string($data) )
			{
				$data = ['content' => $data];
			}

			$this->manifest(ltrim($key, "/"), $data);
			return 'raw';
		}

		$open = $uri->length > 0 && !$uri->isDir;
		$path_prefix = "/";

		// load or create context

		$this->loadContext();
		$context = $this->Context;

		// shift URL path
		if( $context->isPath() )
		{
			$path = $context->getPath();

			// redirect to folder
			if( $open && ("/" . $path) == $uri->path )
			{
				$response
					->redirect( $uri->makeURL( $uri->path . "/" ) )
					->send();

				return $result_type = 'redirect';
			}

			$path_prefix .= $path . "/";
			$uri->shift(substr_count($path, "/") + 1);
		}

		// update system language
		if($context->getType() === "language")
		{
			if( $this->loadIs("Lang") )
			{
				$this->Lang->reload($context->getName());
			}
			else
			{
				EventManager::listen("onLanguage", function(Event $event) {
					if( $event instanceof LanguageEvent ) {
						$event->setParam("language", $this->Context->getName());
					}
				});
			}
		}

		// load default page

		EventManager::dispatch(new LoadEvent());

		// load routers array

		$cache = new Cache('routers');
		if( $cache->ready() )
		{
			$routers = $cache->import();
		}
		else {
			$routers = (new QueryRoutes())
				->get()
				->map(function(RouteSchemeDesigner $scheme) {
					return new MountPoint($scheme);
				})
				->getAll();

			if( count($routers) )
				$cache->export($routers);
		}

		$web_router_404 = false;
		$web_router_found = false;
		$web_page_404 = false;

		/** @var Controller $controller */

		$controller = null;
		$found = false;

		// math

		/** @var MountPoint $mount_point */

		foreach( $routers as $mount_point )
		{
			// if context not use this module
			if( !$context->hasModuleId($mount_point->getModuleId()) )
			{
				continue;
			}

			$type = $mount_point->getType();

			// if controller not found
			if( $type === "404" )
			{
				$web_router_404 = $mount_point;
				if( $found )
				{
					break;
				}
				continue;
			}
			else if( $found )
			{
				continue;
			}

			// redirect to folder
			if( $open && $type == "path" && ($path_prefix . $mount_point->getRule()) == $uri->path )
			{
				$response
					->redirect( $uri->makeURL( $uri->path . "/" ) )
					->send();

				return $result_type = 'redirect';
			}

			// found
			$match = null;
			if( $type == "all" || $type == "index" && $uri->length == 0 || $mount_point->isRule() && $uri->match($type, $mount_point->getRule(), $match) )
			{
				$controller = $this->readyController($mount_point, $match);
				if( $controller !== null )
				{
					$found = true;
					if( $web_router_404 )
					{
						break;
					}
					continue;
				}
				else
				{
					$web_router_found = true;
				}
			}
		}

		if( $controller === null && $web_router_404 !== false )
		{
			$controller = $this->readyController($web_router_404);
			$web_page_404 = true;
		}

		if( $controller === null )
		{
			throw new PageNotFoundException("", $web_router_found ? 404 : 500 );
		}

		$this->changeController($controller);

		$ready = $this->Controller->ready();
		if( ! $ready && ! $web_page_404 && $web_router_404 !== false )
		{
			$controller = $this->readyController($web_router_404);
			if( $controller !== null )
			{
				$this->changeController($controller);
				$ready = $this->Controller->ready();
			}
		}

		if( !$ready )
		{
			throw new \Exception( "Can't load route settings" . ( $this->Controller->hasLogs() ? ": " . $this->Controller->getLastLog() : "" ), 500 );
		}

		$cacheable = $this->Controller->isCacheable();
		$page_cache = false;

		if($cacheable)
		{
			if( $this->Controller instanceof ControllerContentOutput || ! $this->Controller->getId() )
			{
				$cacheable = false;
			}
			else
			{
				$page_cache = new PageCache($this->Controller);
				if( $page_cache->exists() )
				{
					$page_cache->render();
					return $result_type;
				}
			}
		}

		$content_type = $response->headers()->get("Content-Type");

		if( !$content_type )
		{
			$response->header("Content-Type", "text/html; charset=" . BASE_ENCODING);
			$content_type = 'text/html';
		}
		else
		{
			$content_type = preg_split('/[;,\s]+/', ltrim($content_type));
			$content_type = rtrim(strtolower($content_type[0]));
			if( !strlen($content_type) )
			{
				$content_type = 'unknown';
			}
		}

		EventManager::dispatch(new ReadyEvent());

		// custom output data
		if( ! $cacheable )
		{
			$controller = $this->Controller;
			if($controller instanceof ControllerContentOutput && $controller->isRaw())
			{
				$controller->complete();
				$controller->output();

				// send content
				if( !$response->isSent() )
				{
					$response->send();
				}

				return $result_type = 'raw';
			}
		}

		$this->Controller->complete();

		$protected = ["package", "template", "controller", "from_cache"];

		$view = $this->View;
		$data = $this->Controller->getPageData();
		$template = isset( $data["template"] ) ? $data["template"] : "main";

		if( isset($data['package']) )
		{
			$view->usePackage($data['package']);
		}

		$view
			->setProtectedKeys($protected)
			->set($data);

		$Ctrl         = $this->Controller->getProperties();
		$Ctrl['id']   = $this->Controller->getId();
		$Ctrl['name'] = $this->Controller->getName();
		$view->set( 'controller', $Ctrl );

		$onPreRender = new PreRenderEvent(false, $cacheable);
		EventManager::dispatch($onPreRender);

		if($cacheable && $onPreRender->getParam("cacheable") === false)
		{
			$cacheable = false;
		}

		unset($onPreRender);

		$out_page = $view->getTpl( $template );

		// write page cache

		if( $cacheable && ! $page_cache->save($template, $out_page, $content_type) )
		{
			$this->Log->line( "Can't write page cache" );
		}

		$response->setBody( $view->eachPluginData( $out_page, static function( & $info ) { return $info["content"]; }, 3) );

		// complete dispatcher

		EventManager::dispatch(new CompleteEvent($content_type));

		// output data

		$response->send();

		return $result_type;
	}

	/**
	 * Load Controller
	 *
	 * @param Controller $controller
	 * @return Controller
	 * @throws \Exception
	 */
	public function changeController( Controller $controller )
	{
		if( isset($this->ci["Controller"]) && method_exists( $this->ci["Controller"], "change" ) && ! $this->ci["Controller"]->change() )
		{
			throw new \Exception("Current controller '" . get_class( $this->ci["Controller"] ) . "' does not allow change");
		}

		$this->ci["Controller"] = $controller;
		$this->Controller = $controller;

		return $this->Controller;
	}

	public function loadIs( $name, $auto_load = false )
	{
		if( isset($this->ci[$name]) )
		{
			return true;
		}

		if( $auto_load )
		{
			try {
				$this->load($name);
			}
			catch( NotFoundException $e ) {
				return false;
			}
		}
		else {
			return false;
		}

		return true;
	}

	public function loadContext()
	{
		if( isset($this->ci["Context"]) )
			return $this->ci["Context"];

		if( CONSOLE_MODE )
			throw new \InvalidArgumentException("Cannot use context for cli mode");

		$cache = new Cache("context");

		/** @var Context $context */
		/** @var Context[] $ctx */
		/** @var array $item */
		/** @var ContextSchemeDesigner $instance */

		if( $cache->ready() )
		{
			$ctx = $cache->import();
			foreach($ctx as $item)
				$ctx[$item["name"]] = Context::createFromData($item);
		}
		else
		{
			$ctx = [];
			$cache_data = [];

			foreach( (new QueryContext())->get() as $instance)
			{
				$context_item = Context::createFromSchemeDesignerInstance($instance);
				$ctx[$instance->name] = $context_item;
				$cache_data[] = $context_item->toArray();
			}

			if( count($cache_data) )
				$cache->export($cache_data);
		}

		function isQuery( array $query )
		{
			foreach($query as $name => $value)
			{
				if( !isset($_GET[$name]) )
				{
					return false;
				}
				if( is_array($value) )
				{
					if( ! in_array($_GET[$name], $value) )
					{
						return false;
					}
				}
				else if( strlen($value) && $_GET[$name] !== $value )
				{
					return false;
				}
			}

			return true;
		}

		$priority_host  = false;
		$priority_path  = false;
		$priority_query = false;
		$collection     = new Collection();
		$path           = $this->Uri->length > 0 ? (implode("/", $this->Uri->segment) . "/") : "";

		foreach($ctx as $name => $context_item)
		{
			$is_host    = $context_item->isHost();
			$is_path    = $context_item->isPath();
			$is_query   = $context_item->isQuery();
			$is_once    = $is_host || $is_path || $is_query;

			if($is_once)
			{
				$collection[] = $context_item;
			}

			if(
				$is_host  && $context_item->getHost() !== APP_HOST ||
				$is_path  && ! ( $path && strpos($path, $context_item->getPath() . "/") === 0 ) ||
				$is_query && ! isQuery( $context_item->getQuery() ) ||
				$priority_host  && ! $is_host ||
				$priority_path  && ! ($is_path || $is_host) ||
				$priority_query && ! $is_once ||
				$is_once && ! $context_item->isDefault()
			)
			{
				continue;
			}

			$context        = $context_item;
			$priority_host  = $is_host;
			$priority_path  = $is_path;
			$priority_query = $is_query;
		}

		if( ! isset($context) )
		{
			throw new NotFoundException("System context not found");
		}

		$event = new ContextEvent($context, $collection);
		EventManager::dispatch($event);

		$this->ci["Context"] = $event->getParam("context");
		$this->Context = $this->ci["Context"];
		return $this->ci["Context"];
	}

	public function load( $name )
	{
		static $init = false;
		static $ci = [];
		static $reserved = ['Lang', 'Log', 'PhpExport', 'Session', 'Uri', 'Context', 'Controller'];

		if( ! $init )
		{
			$init = true;
			$ci = Prop::file("ci");
			$this->ci['Log'] = CiLog::getInstance();
			$this->ci['Response'] = new Response();
			$this->ci['Request'] = Request::createFromGlobals();

			// reserved
			$ci['Database'] = DataBaseManager::class;
			$ci['Filesystem'] = Filesystem::class;
			$ci['View'] = View::class;

			$event = new SingletonEvent();
			EventManager::dispatch($event);

			foreach($event as $n => $s)
			{
				if( isset($ci[$n]) || isset($this->ci[$n]) || in_array($n, $reserved, true) )
				{
					throw new \InvalidArgumentException("Duplicated class name '{$n}' for singleton instance object");
				}

				if( is_object($s) )
				{
					$this->ci[$n] = $s;
					if( $s instanceof SingletonCompletable )
					{
						$s->instanceComplete($this);
					}
				}
				else if( is_string($s) )
				{
					$ci[$n] = $s;
				}
				else
				{
					throw new \InvalidArgumentException("Invalid object type for the '{$n}' singleton object");
				}
			}

			unset($event, $item, $n, $s);
		}

		if( ! isset( $this->ci[$name] ) )
		{
			if( $name == "Context" )
			{
				return $this->loadContext();
			}

			$className = isset( $ci[$name] ) ? $ci[$name] : "EApp\\CI\\" . $name;
			if( !class_exists($className, true) )
			{
				throw new NotFoundException("Singleton class '{$name}' not found.");
			}

			$this->ci[$name] = (new \ReflectionMethod($className, 'getInstance'))->invoke(null);
			$this->{$name} = $this->ci[$name];

			if( $this->ci[$name] instanceof SingletonCompletable )
			{
				$this->ci[$name]->instanceComplete($this);
			}
		}
		else if( ! isset($this->{$name}) )
		{
			$this->{$name} = $this->ci[$name];
		}

		return $this->ci[$name];
	}

	public function close()
	{
		static $close = false;
		if( $close ) {
			return;
		}

		$close = true;

		// rollback database transaction
		if( \DB::hasInstance() )
		{
			$conn = \DB::connection();
			$conn->transactionLevel() > 0 && $conn->rollBack();
		}

		$err = error_get_last();
		if( is_array($err) && $err['type'] != E_NOTICE && $err['type'] != E_USER_NOTICE )
		{
			$this->Log->line(
				"PHP shutdown error: " . trim($err['message']) .
				", file: " . $err['file'] .
				", line: " . $err['line']
			);
		}

		// write logs
		if( $this->loadIs('Log') )
		{
			$this->Log->flush();
		}

		// shutdown callback
		EventManager::dispatch(new ShutdownEvent());
	}

	public function __get( $name )
	{
		return $this->load($name);
	}

	public function __destruct()
	{
		$this->close();
	}

	public function __call($name, array $arguments)
	{
		$instance = $this->load($name);
		if( method_exists($instance, '__invoke') )
		{
			return $instance(...$arguments);
		}

		throw new \InvalidArgumentException("Invalid parameters of the '{$name}' instance.");
	}

	public static function __callStatic( $name, array $arguments )
	{
		return count($arguments) ? self::getInstance()->__call($name, $arguments) : self::getInstance()->load($name);
	}

	// private

	private function manifest( $key, $data )
	{
		$mnf = new Prop($data);
		$response = $this->Response;

		// headers
		if( $mnf->isArray('headers') )
		{
			foreach($mnf->get('headers') as $name => $header)
			{
				if( is_int($name) )
				{
					$response->header($header);
				}
				else
				{
					$response->header($name, $header);
				}
			}
		}
		else if( $mnf->getIs('header') )
		{
			$response->header($mnf->get('header'));
		}

		// add cache header
		// cache or no cache ?
		if( $mnf->equiv('no_cache', true) )
		{
			$response->noCache();
		}
		else
		{
			$response->cache( $mnf->getOr('cache', null) );
		}

		// content
		if( $mnf->getIs('content') )
		{
			$response->setBody($mnf->get('content'));
		}
		else
		{
			$file = APP_DIR . $mnf->getOr('file', $key);
			if( file_exists($file) )
			{
				$response->file($file);
			}
		}

		if( !$response->isSent() )
		{
			$response->send();
		}
	}

	private function readyController( MountPoint $mount_point, $match = null )
	{
		$class_name = $mount_point->getModule()->getNameSpace() . 'Router';

		/** @var Router $router */

		$router = new $class_name( $mount_point, $match );

		if( $router instanceof Router )
		{
			if( $router->ready() )
			{
				$controller = $router->getController();
				if( ! is_object($controller) )
				{
					throw new \RuntimeException("Router controller method mast be return controller object", 500);
				}

				if( $controller instanceof Controller )
				{
					return $controller;
				}

				throw new \RuntimeException("Controller must be inherited of " . Controller::class, 500);
			}
		}
		else {
			throw new \RuntimeException("Router must be inherited of " . Router::class, 500);
		}

		return null;
	}
}