<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 16:39
 */

namespace EApp\View;

use EApp\App;
use EApp\Cache;
use EApp\Event\EventManager;
use EApp\Controllers\Controller;
use EApp\Support\Str;
use EApp\Events\CompleteEvent;
use EApp\Events\PreRenderEvent;
use EApp\Events\ReadyEvent;

class PageCache
{
	protected $exists = false;

	/**
	 * @var Cache
	 */
	protected $cache;

	protected $page_template = "main";
	protected $page_headers = [];
	protected $page_protected = [];
	protected $page_content_type = "text/html";
	protected $page_body = "";
	protected $page_data = [];
	protected $page_plugins = [];

	public function __construct( Controller $controller )
	{
		$name = "page-" . $controller->getId();
		$prop = $controller->getProperties();
		if( empty($prop['prefix']) )
		{
			$prefix = str_replace( ':', '_', str_replace( '::', '/', $controller->getName() ) );
		}
		else
		{
			$prefix = $prop["prefix"];
		}

		unset( $prop['prefix'] );

		$this->cache = new Cache($name, $prefix, $prop);
		if( $this->cache->ready() )
		{
			$data = $this->cache->import();
			if( isset($data["template"], $data["body"], $data["data"], $data["headers"], $data["protected"], $data["content_type"], $data["plugins"]) )
			{
				$this->exists = true;

				$this->page_template = $data["template"];
				$this->page_headers = $data["headers"];
				$this->page_protected = $data["protected"];
				$this->page_content_type = $data["content_type"];
				$this->page_body = $data["body"];
				$this->page_data = $data["data"];
				$this->page_plugins = $data["plugins"];
			}
			else
			{
				App::Log("Invalid data import for page cache by the " . $controller->getName() . " controller, page " . $controller->getId());
			}
		}
	}

	public function exists(): bool
	{
		return $this->exists;
	}

	public function render()
	{
		if( ! $this->exists() )
		{
			throw new \InvalidArgumentException("Cache data is not exists");
		}

		$app = App::getInstance();

		$view = $app->View;
		$view->set( $this->page_data );
		$view->set( "from_cache", true );
		$view->setProtectedKeys($this->page_protected);

		EventManager::dispatch(new ReadyEvent(true));
		EventManager::dispatch(new PreRenderEvent(true));

		$app->Response->setBody(
			$view->eachPluginData(
				$view->getTplClosure(
					$this->page_template,
					function(View $view) {
						return preg_replace_callback('/\{plugin:(\d+):(.*?)\}/', function($m) use ($view) {

							$id = (int) $m[1];
							if( isset( $this->page_plugins[$id] ) && $m[2] === $this->page_plugins[$id]["hash"] )
							{
								$info = $this->page_plugins[$id];
								$data = $info["data"];

								$data["cache"] = "nocache";

								if( $info["cache"] == "plugin" )
								{
									$name = Str::snake($info["name"]);
									$cache_data = $info["cache_data"];
									$cache_name = $name;

									if( isset($cache_data["id"]) )
									{
										$cache_name = $cache_data["id"];
										unset($cache_data["id"]);
									}

									$cache = new Cache( $cache_name, 'plugin_' . $name, $cache_data );
									if($cache->ready())
									{
										return $cache->get();
									}

									$plugin_content = $view->getPlugin( $info["name"], $data );
									$cache->set($plugin_content);

									return $plugin_content;
								}

								else {
									// no cache
									return $view->getPlugin( $info["name"], $data );
								}
							}
							else
							{
								return $m[0];
							}
						}, $this->page_body);
					}
				),
				static function( & $info ) { return $info["content"]; }, 3
			)
		);

		EventManager::dispatch(new CompleteEvent($this->page_content_type, true));

		$app->Response->send();
	}

	public function save( string $template, string $page_body, string $content_type = "text/html" ): bool
	{
		$app = App::getInstance();
		$php_export = $app->PhpExport;
		$view = $app->View;

		$data = [
			"template"      => $template,
			"headers"       => $app->Response->headers()->toArray(),
			"protected"     => $view->getProtectedKeys(),
			"content_type"  => $content_type,
			"plugins"       => []
		];

		$all = $view->toArray();
		unset($all["from_cache"], $all["post"]);

		$data["data"] = $all;
		$data["body"] = $view->eachPluginData( $page_body, function( $info ) use ( $php_export, & $data ) {
			static $plugin_index = 0;
			if( $info["cache"] == "page" )
			{
				return $info["content"];
			}
			else {
				$num = $plugin_index ++;
				unset($info["content"]);
				$data["plugins"][$num] = $info;
				return "{plugin:{$num}:{$info['hash']}";
			}
		});

		return $this->cache->export($data);
	}
}