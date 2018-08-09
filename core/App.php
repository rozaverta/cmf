<?php

namespace EApp;

use EApp\CI\Log as CiLog;
use EApp\Component\Context;
use EApp\Component\QueryContext;
use EApp\Component\Scheme\ContextSchemeDesigner;
use EApp\Database\Manager as DataBaseManager;
use EApp\Event\Event;
use EApp\Event\EventManager;
use EApp\Http\Request;
use EApp\Http\Response;
use EApp\Proto\Controller;
use EApp\Proto\Router;
use EApp\Support\Collection;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Exceptions\PageNotFoundException;
use EApp\Support\Str;
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
 * @property \EApp\CI\View View
 * @property \EApp\CI\Lang Lang
 * @property \EApp\CI\Session Session
 * @property Controller Controller
 * @property Context Context
 *
 * @property \EApp\Http\Response Response
 * @property \EApp\Http\Request Request
 *
 * @method static \EApp\CI\Log Log(...$args)
 * @method static \EApp\CI\PhpExport PhpExport()
 * @method static \EApp\CI\Uri Uri()
 * @method static \EApp\CI\View View()
 * @method static \EApp\CI\Lang Lang(...$args)
 * @method static \EApp\CI\Session Session(...$args)
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

	const VER = "1.0.0";

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

		$sys = new Prop("system");

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
			\E\IncludeFile($file, ['app' => $this]);
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

		if( ! $sys->equiv("version", self::VER) )
		{
			throw new \Exception("The current version of the system does not match the previously specified");
		}

		// load manifest data

		$uri = $this->Uri;
		$mnf = new Prop('manifest');
		$response = $this->Response;

		if( $mnf->count() && $uri->mode() == 'rewrite' && $uri->length > 0 && $mnf->getIs($uri->path) )
		{
			$key  = $uri->path;
			$data = \E\Value( $mnf->get($key) );

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
			$routers = $cache->getContentData();
		}
		else {
			$routers = (new QueryRoutes())
				->get()
				->toArray();

			if( count($routers) ) {
				$cache->write($routers);
			}
		}

		$web_router_404 = false;
		$web_router_found = false;
		$web_page_404 = false;

		/** @var Controller $controller */

		$controller = null;
		$found = false;

		// math

		foreach($routers as $item)
		{
			// if context not use this module
			if( !$context->hasModuleId($item["module_id"]) )
			{
				continue;
			}

			$type = (string) $item['type'];

			// if controller not found
			if( $type === "404" )
			{
				$web_router_404 = $item;
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
			if( $open && $type == "path" && ($path_prefix . $item['path']) == $uri->path )
			{
				$response
					->redirect( $uri->makeURL( $uri->path . "/" ) )
					->send();

				return $result_type = 'redirect';
			}

			// found
			$match = null;
			if( $type == "all" || $type == "index" && $uri->length == 0 || isset($item[$type]) && $uri->match($type, $item[$type], $match) )
			{
				$controller = $this->readyController($item, $type, isset($item[$type]) ? $item[$type] : '', $match);
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

		$cacheable = $this->Controller->cacheable();
		if($cacheable && $this->Controller instanceof ControllerContentOutput)
		{
			$cacheable = false;
		}

		$cache = false;

		if( $cacheable )
		{
			$cacheID = $this->Controller->getId();
			if( !$cacheID )
			{
				$cacheable = false;
			}
			else
			{
				$cData = $this->Controller->properties();
				$cDir = 'website_pages/' . ( empty($cData['directory']) ? str_replace( ':', '_', str_replace( '::', '/', $this->Controller->name() ) ) : $cData['directory'] );
				unset( $cData['directory'] );

				$cache = new Cache( $cacheID, $cDir, $cData );
				if( $cache->ready() )
				{
					EventManager::dispatch(new ReadyEvent(true));

					$view = $this->View;
					$view->set( "from_cache", true );

					EventManager::dispatch(new PreRenderEvent(true));

					\E\IncludeFile( $cache->path(), ["app" => $this, "view" => $view] );

					return $result_type;
				}
			}
		}

		$outCache = "\n";
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

		if( $cacheable )
		{
			foreach( $response->headers() as $name => $value )
			{
				$outCache .= '$app->Response->header(' . $this->PhpExport->string( $name ) . ', ' . $this->PhpExport->string( $value ) . ");\n";
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
		$data = $this->Controller->pageData();
		$template = isset( $data["template"] ) ? $data["template"] : "main";

		if( isset($data['package']) )
		{
			$view->usePackage($data['package']);
		}

		$view
			->setKeysAsProtected($protected)
			->set($data);

		$Ctrl         = $this->Controller->properties();
		$Ctrl['id']   = $this->Controller->getId();
		$Ctrl['name'] = $this->Controller->name();
		$view->set( 'controller', $Ctrl );

		$onPreRender = new PreRenderEvent(false, $cacheable);
		EventManager::dispatch($onPreRender);

		if($cacheable && $onPreRender->getParam("cacheable") === false) {
			$cacheable = false;
		}

		unset($onPreRender);

		$outPage = $view->getTpl( $template );

		// write page cache
		if( $cacheable )
		{
			$php = $this->PhpExport;
			$escape = static function( $value ) {
				return str_replace(
					[ '<?', '?>' ],
					[ '<?= "<"; ?>?', '?<?= ">"; ?>' ],
					$value
				);
			};

			$all = $view->toArray();
			unset($all["from_cache"]);

			$outCache .= '$view->setKeysAsProtected("' . implode('", "', $protected) . "\");\n";
			$outCache .= '$view->set(';
			$outCache .= $php->assoc( $view->toArray() ) . '); ob_start(); ?>';
			$outCache .= $escape( $outPage );

			$view->eachPluginData( $outCache, function( & $info ) use ( $escape, $php ) {

				if( $info["cache"] == "page" )
				{
					return $escape( $info["content"] );
				}

				else if( $info["cache"] == "plugin" )
				{
					$name = Str::snake($info["name"]);
					$id = $name;
					if( isset($info["cacheData"]["id"]) )
					{
						$id = $info["cacheData"]["id"];
						unset($info["cacheData"]["id"]);
					}

					$str  = '<?php ';
					$str .= "\n\$cache = new \\EApp\\Cache( " . $php->string( $id ) . ", ";
					$str .= $php->string( 'plugin_' . $name ) . ", " . $php->php( $info["cacheData"] );
					$str .= " );\nif( \$cache->ready() ) echo \$cache->getContentData();\nelse {\n\t";
					$str .= "\$cacheData = \$view->getPlugin( " . $php->string( $info["name"] ) ;
					$str .= ", " . $php->php( $info["data"] ) . ", true );\n\t";
					$str .= "\$cache->write( \$cacheData );\n\techo \$cacheData;\n}\n";
					$str .= 'unset( $cache, $cacheData ); ?>';
					return $str;
				}

				else {
					// no cache
					return
						'<?= $view->getPlugin( ' .
						$php->string( $info["alias"] ) . ', ' .
						$php->php( $info["data"] ) . ', true ); ?>'
						;
				}
			});

			$outCache .= '<?php ' . "\n" .
				'$html = ob_get_contents(); ob_end_clean();' . "\n" .
				'$app->Response->setBody($view->replaceDelay( $html ));' . "\n" .
				'\\EApp\\Event\\EventManager::dispatch("onSystem", new \\EApp\\System\\Events\\CompleteEvent(' . var_export($content_type, true) . ', true));' . "\n" .
				'$app->Response->send();';

			if( !$cache->writePhp($outCache) )
			{
				$this->Log->line( "Can't write page cache" );
			}

			unset( $outCache );
		}

		$response->setBody( $view->eachPluginData( $outPage, static function( & $info ) { return $info["content"]; }, 3) );

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
			$ctx = $cache->getContentData();
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
				$cache->write($cache_data);
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
		static $reserved = ['Lang', 'Log', 'PhpExport', 'Session', 'Uri', 'View', 'Context', 'Controller'];

		if( ! $init )
		{
			$init = true;
			$ci = Prop::file("ci");
			$this->ci['Log'] = CiLog::getInstance();
			$this->ci['Response'] = new Response();
			$this->ci['Request'] = Request::createFromGlobals();

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
		if( \DB::loaded() )
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

	private function readyController( $item, $type = "404", $mask = '', $match = null )
	{
		$module = Module::cache($item['module_id']);
		$class  = $module->get('name_space') . 'Router';

		/** @var Router $router */

		$router = new $class( $module, $item['properties'], $type, $mask, $match, $item['id'] );

		if( $router instanceof Router )
		{
			if( $router->ready() )
			{
				$controller = $router->controller();
				if( ! is_object($controller) )
				{
					throw new \Exception("Router controller method mast be return controller object", 500);
				}

				if( $controller instanceof Controller )
				{
					return $controller;
				}

				throw new \Exception("Controller must be inherited of \\EApp\\Proto\\Controller", 500);
			}
		}
		else {
			throw new \Exception("Router must be inherited of \\EApp\\Proto\\Router", 500);
		}

		return null;
	}
}
