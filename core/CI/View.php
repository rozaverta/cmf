<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 16:37
 */

namespace EApp\CI;

use EApp\App;
use EApp\Cache;
use EApp\Event\EventManager;
use EApp\Plugin\QueryPlugins;
use EApp\Plugin\Interfaces\PluginShotable;
use EApp\Plugin\Scheme\PluginSchemeDesigner;
use EApp\Prop;
use EApp\Proto\Plugin;
use EApp\Support\Interfaces\SingletonCompletable;
use EApp\Support\Str;
use EApp\Support\Traits\Set;
use EApp\System\Events\RenderCompleteEvent;
use EApp\System\Events\RenderGetOnEvent;
use EApp\System\Events\ShutdownEvent;
use EApp\System\Events\SystemEvent;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\Compare;
use EApp\Support\Traits\SingletonInstance;
use EApp\Template\Package;
use EApp\Template\QueryIncludes;
use EApp\Template\QueryPackages;
use ArrayAccess;

/**
 * Class Log
 * @package CI
 * @method static View getInstance()
 */
final class View implements SingletonCompletable, ArrayAccess
{
	use SingletonInstance;
	use Get;
	use Set;
	use Compare;

	protected $items = [];

	private $getDelay = [];
	private $iterationLimit = 10;
	private $plugin = [];
	private $plugin_index = 0;
	private $shortTags = [];

	private $http = '/^(?:\/\/|https?:)/';
	private $charset;

	/**
	 * @var \EApp\Template\Package
	 */
	private $package;

	/**
	 * @var null | \EApp\Template\Template
	 */
	private $template = null;

	private $packages = [];

	private $plugins = [];

	private $call = [];

	private $protected = [];

	public function __construct( $conf = [] )
	{
		$app = App::getInstance();
		$url = $app->Uri;

		$this->items["language"]   = $app->Lang->getCurrent();
		$this->items["site_name"]  = Prop::cache("system")->getOr("site_name", APP_HOST);
		$this->items["page_title"] = $conf['page_title'] ?? $this->items["site_name"];
		$this->items["assets"]     = $conf['assets']     ?? $url->base . ltrim( ASSETS_PATH, "/" );
		$this->items["charset"]    = $this->charset = BASE_ENCODING;
		$this->items["now"]        = time();
		$this->items["from_cache"] = false;
		$this->items["host"]       = $url->host;
		$this->items["http"]       = BASE_PROTOCOL . "://" . $url->host;

		// route

		$this->items["route_url"]   = $url->url;
		$this->items["route_base"]  = $url->base;
		$this->items["route_path"]  = $url->path;

		$this->protected = array_keys($this->items);
		$this->protected[] = "package";
		$this->protected[] = "template";
		$this->protected[] = "controller";
		$this->protected[] = "from_cache";

		// load plugins

		$cache = new Cache("plugins", 'template');
		if( $cache->ready() )
		{
			$this->plugins = $cache->getContentData();
		}
		else
		{
			foreach((new QueryPlugins())->filter("visible", true)->orderBy("name")->get() as $item)
			{
				$load = $this->loadPlugin($item);
				if($load)
				{
					$this->plugins[$item->name] = $load;
				}
			}

			$cache->write($this->plugins);
		}

		foreach($this->plugins as $plugin)
		{
			if($plugin["short"])
			{
				$this->shortTags[$plugin["short"]] = $plugin["class_name"];
			}
		}
	}

	protected function loadPlugin( PluginSchemeDesigner $item )
	{
		$class_name = $item->class_name;
		if( !class_exists($class_name, true) )
		{
			App::Log("Plugin load error, class '{$class_name}' not found");
			return false;
		}

		$ref = new \ReflectionClass($class_name);

		$found = false;
		$parent = $ref;
		$instance = Plugin::class;
		while( $parent = $parent->getParentClass() )
		{
			$found = $parent->getName() === $instance;
			if($found)
			{
				break;
			}
		}

		if( !$found )
		{
			App::Log("Plugin load error, class '{$class_name}' must be inherited of {$instance}");
			return false;
		}

		$short = false;
		if( in_array( PluginShotable::class, $ref->getInterfaces()) )
		{
			$short = $ref->getMethod('getShotName')->invoke(null);
		}

		return [
				"package_name" => $item->package_name,
				"name" => $item->name,
				"short" => $short,
				"class_name" => $class_name
			];
	}

	public function __call( $name, $arguments )
	{
		if( isset($this->call[$name]) )
		{
			return $this->call[$name]( $this, ...$arguments );
		}
		else
		{
			throw new \InvalidArgumentException("Method not used '$name'");
		}
	}

	public function register( $name, \Closure $callback )
	{
		$this->call[$name] = $callback;
		return $this;
	}

	public function getPackageInstance($name, array $conf = [])
	{
		$instance = new self($conf);
		$instance->packages = $this->packages;
		$instance->shortTags = $this->shortTags;
		return $instance->usePackage($name);
	}

	/**
	 * @return \EApp\Template\Package
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * @return \EApp\Template\Template|null
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	public function instanceComplete( App $app )
	{
		static $complete = false;
		if( $complete ) {
			throw new \Exception("The view pack instance has already been completed.");
		}

		$complete = true;

		// load packages IDs
		$cache = new Cache('id_from_name', 'template/package');
		if( $cache->ready() )
		{
			$this->packages = $cache->getContentData();
		}
		else
		{
			/** @var \EApp\Template\Scheme\PackageSchemeDesigner $item */
			foreach( (new QueryPackages())->get() as $item )
			{
				$this->packages[$item->name] = $item->id;
			}
			$cache->write($this->packages);
		}

		$this->usePackage( Prop::cache("system")->getOr("package", "main") );

		$cache = new Cache('includes', 'template');
		if( $cache->ready() )
		{
			$inc = $cache->getContentData();
		}
		else
		{
			$inc = [];
			/** @var \EApp\Template\Scheme\IncludeSchemeDesigner $item */
			foreach( (new QueryIncludes())->get() as $item )
			{
				$inc[] = $item->full_path;
			}
			$cache->write($inc);
		}

		if( count($inc) )
		{
			foreach($inc as $file)
			{
				\E\IncludeFile($file, ["view" => $this], false, true);
			}
		}

		unset($cache, $inc);
	}

	public function setKeysAsProtected($keys)
	{
		if( !is_array($keys) ) {
			$keys = [$keys];
		}

		foreach($keys as $key) {
			if( ! in_array($key, $this->protected, true) ) {
				$this->protected[] = $key;
			}
		}

		return $this;
	}

	public function getIsProtectedKey($name)
	{
		if( substr($name, 0, 2) === "__" ) {
			return true;
		}
		else {
			return in_array($name, $this->protected, true);
		}
	}

	// global data

	public function concat( $name, $value, $separate = "", $before = false )
	{
		if( ! array_key_exists( $name, $this->items ) ) {
			$this->items[$name] = $value;
		}
		else if( $before ) {
			if( is_array( $this->items[$name] ) ) {
				if( $separate ) {
					$row = [];
					$row[$separate] = $value;
					$this->items[$name] = $row + $this->items[$name];
				}
				else {
					array_unshift( $this->items[$name], $value );
				}
			}
			else {
				$this->items[$name] = $value . $separate . $this->items[$name];
			}
		}
		else if( is_array( $this->items[$name] ) ) {
			if( $separate ) {
				$this->items[$name][$separate] = $value;
			}
			else {
				$this->items[$name][] = $value;
			}
		}
		else {
			$this->items[$name] .= $separate . $value;
		}
		return $this;
	}

	public function getDelay( $name )
	{
		if( !isset($this->getDelay[$name]) )
		{
			$this->getDelay[$name] = md5(mt_rand());
		}
		return '{item:' . $name . ':' . $this->getDelay[$name] . '}';
	}

	public function postValue( $name, $value = '', $escape = true )
	{
		if( isset($this->items['post'][$name]) )
		{
			$value = $this->items['post'][$name];
		}
		return $escape ? htmlspecialchars($value) : $value;
	}

	public function getPath( $path, $default = "" )
	{
		if( strpos( $path, "->" ) !== false )
		{
			$path = explode( "->", $path );
			if( !isset( $this->items[$path[0]]) ) {
				return $default;
			}

			$len = count( $path );
			if( $len == 2 )
			{
				return isset( $this->items[$path[0]][$path[1]] ) ? $this->items[$path[0]][$path[1]] : $default;
			}
			if( $len == 3 )
			{
				return isset( $this->items[$path[0]][$path[1]][$path[2]] ) ? $this->items[$path[0]][$path[1]][$path[2]] : $default;
			}

			$found =& $this->items[$path[0]];

			for( $i = 1; $i < $len; $i++ )
			{
				if( !isset( $found[$path[$i]] ) ) {
					return $default;
				}
				else if( is_array($found) ) {
					$saved =& $found[$path[$i]];
					unset( $found );
					$found =& $saved;
					unset( $saved );
				}
				else {
					$found = $found[$path[$i]];
				}
			}
		}
		else {
			return $this->getOr($path, $default);
		}

		return $found;
	}

	/**
	 * Use RenderGetOnEvent event before return item value
	 *
	 * @example <?= $view->getOn("content") ?>
	 *
	 * @param string $name name or path
	 * @param string $default default result value
	 * @return mixed
	 */
	public function getOn( string $name, $default = "" )
	{
		$event = new RenderGetOnEvent( $name, $this->getPath($name, $default) );
		EventManager::dispatch($event);
		return $event->getParam("value");
	}

	/**
	 * Get an item from the collection by keys if value not empty.
	 *
	 * @param array $keys
	 * @param mixed $default default value
	 * @return mixed
	 */
	public function fillChoice( array $keys, $default = "" )
	{
		foreach( $keys as $key )
		{
			if( $this->isFill($key) )
			{
				return $this->items[$key];
			}
		}

		return $default;
	}

	// assets

	public function assets( $file, $full = false )
	{
		return ( $full ? $this->items['http'] : '' ) . $this->items["assets"] . $file;
	}

	public function getScript( $file, $prop = [] )
	{
		$dir = isset($prop["dir"]) ? trim($prop["dir"], "/") : "js";
		$src = $this->items["assets"] . ( $dir ? $dir . "/" : "" );
		$nln = isset($prop["nl"]) ? $prop["nl"] : "\n";

		if( $dir ) {
			$dir .= DIRECTORY_SEPARATOR;
		}
		if( isset($prop['full']) && $prop['full'] ) {
			$src = $this->items['http'] . $src;
		}

		$get = '';
		$ver = isset( $prop["version"] ) ? $prop["version"] : false;
		$srv = $ver === true || $ver === "auto";
		$sct = isset( $prop["type"] ) ? ' type="' . $prop["type"] . '"' : '';

		if( isset( $prop["charset"] ) && $prop["charset"] ) {
			$sct .= ' charset="';
			$sct .=  $prop["charset"] === true || strtoupper($prop["charset"]) === 'AUTO' ? $this->items['charset'] : $prop["charset"];
			$sct .= '"';
		}

		if( isset( $prop["language"] ) && $prop["charset"] ) {
			$sct .= ' language="';
			$sct .=  $prop["language"] === true || strtoupper($prop["language"]) === 'AUTO' ? 'JavaScript' : $prop["language"];
			$sct .= '"';
		}

		if( !is_array( $file ) ) {
			$file = [$file];
			$len = 1;
		}
		else {
			$len = count( $file );
		}

		for( $i = 0; $i < $len; $i++ ) {

			$srcValue = $file[$i];
			$get .= '<script' . $sct . ' src="';

			if( preg_match( $this->http, $srcValue ) ) {
				$get .= $srcValue;
			}
			else {
				$get .= $this->_srcRelativate( $src . $srcValue );
				if( $ver ) {
					if( $srv ) $get .= $this->_ver( $this->package->get('assets_path') . $dir . $file[$i] );
					else $get .= "?v=" . $ver;
				}
			}

			$get .= '"></script>' . $nln;
		}

		if( isset($prop['trim']) ) {
			$trim = $prop['trim'] === true || strtoupper($prop['trim']) === 'AUTO' ? null : $prop['trim'];
			$get = trim($get, $trim);
		}

		return $get;
	}

	public function getCss( $file, $prop = [] )
	{
		$dir = isset($prop["dir"]) ? trim($prop["dir"], "/") : "css";
		$src = $this->items["assets"] . ( $dir ? $dir . "/" : "" );
		$nln = isset( $prop["nl"] ) ? $prop["nl"] : "\n";

		if( $dir ) {
			$dir .= DIRECTORY_SEPARATOR;
		}
		if( isset($prop['full']) && $prop['full'] ) {
			$src = $this->items['http'] . $src;
		}

		$get = '';
		$ver = isset( $prop["version"] ) ? $prop["version"] : false;
		$srv = $ver === true || $ver === "auto";

		$attr = '';
		if( !isset($prop["type"]) ) $attr .= ' type="text/css"';
		else if( !empty($prop["type"]) ) $attr .= ' type="' . $prop["type"] . '"';

		if( !isset($prop["media"]) ) $attr .= ' media="all"';
		else if( !empty($prop["media"]) ) $attr .= ' media="' . $prop["media"] . '"';

		if( !isset($prop["rel"]) ) $attr .= ' rel="stylesheet"';
		else if( !empty($prop["rel"]) ) $attr .= ' rel="' . $prop["rel"] . '"';

		if( !is_array( $file ) ) {
			$file = [$file];
			$len = 1;
		}
		else {
			$len = count( $file );
		}

		for( $i = 0; $i < $len; $i++ ) {

			$get .= '<link' . $attr . ' href="';
			$srcValue = $file[$i];

			if( preg_match( $this->http, $srcValue ) ) {
				$get .= $srcValue;
			}
			else {
				$get .= $this->_srcRelativate( $src . $srcValue );
				if( $ver ) {
					if( $srv ) $get .= $this->_ver( $this->package->get('assets_path') . $dir . $file[$i] );
					else $get .= "?v=" . $ver;
				}
			}

			$get .= '" />' . $nln;
		}

		if( isset($prop['trim']) ) {
			$trim = $prop['trim'] === true || strtoupper($prop['trim']) === 'AUTO' ? null : $prop['trim'];
			$get = trim($get, $trim);
		}

		return $get;
	}

	private function _srcRelativate( $url )
	{
		for(;;)
		{
			$eot = strpos( $url, '/../' );
			if( $eot ) {
				$pos = strrpos( $url, "/", $eot - strlen($url) - 1 );
				if( $pos !== false ) {
					$url = substr_replace( $url, '/', $pos, $eot - $pos + 4 );
					continue;
				}
			}
			break;
		}

		return $url;
	}

	public function getImg( $img, $prop = [] )
	{
		$baseDir = false;
		if( $img[0] === '/' && substr($img, 0, 2) !== '//' ) {
			$img = ltrim($img, '/');
			$baseDir = true;
		}

		if( $prop === 'src' ) {
			return $this->items["assets"] . ( $baseDir ? '' : 'images/' ) . $img;
		}

		$srv = !preg_match( $this->http, $img );
		$ins = $this->package->get('assetsPath');
		if( $srv ) {

			$assets = $this->items["assets"];
			if( isset( $prop['dir'] )) {
				if( !empty($prop['dir']) ) {
					$assets .= $prop['dir'] . "/";
					$ins .= $prop['dir'] . DIRECTORY_SEPARATOR;
				}
			}
			else if( !$baseDir ) {
				$assets .= 'images/';
				$ins .= 'images' . DIRECTORY_SEPARATOR;
			}

			if( isset( $prop['full'] ) ) {
				if( $prop['full'] && $assets[0] == '/' ) {
					$assets = $this->items['http'] . $assets;
				}
				unset( $prop['full'] );
			}

			$ins = $ins . $img;
			$img = $assets . $img;
			unset( $prop['dir'], $prop['full'], $assets );
		}

		$get = '<img src="' . $img . '"';

		$size = $prop === 'size';
		if( is_array($prop) ) {
			foreach( $prop as $name => $value ) {
				if( !is_numeric($name) ) {
					$get .= ' ' . $name . '="' . htmlspecialchars( $value ) . '"';
				}
				else if( $value === "size" ) {
					$size = true;
				}
			}
		}

		if( $srv && $size && file_exists( $ins ) ) {
			$size = @ getimagesize( $ins );
			$get .= ' ' . $size[3];
		}

		return $get . ' />';
	}

	// template

	public function usePackage( $name )
	{
		$name = trim($name);
		if( !isset($this->packages[$name]) ) {
			throw new \InvalidArgumentException("Package '{$name}' not found.");
		}

		$this->package = new Package( $this->packages[$name] );
		$this->items['assets'] = $this->package->get('assets');
		$this->items['package'] = $name;
		$this->package->func();

		return $this;
	}

	public function getTplCache( $name, $path )
	{
		//
	}

	public function getTpl( $name, $local = null )
	{
		static $func, $level = 0, $init = false, $current_path = "";

		if( !$init )
		{
			$init = true;
			$app  = App::getInstance();
			$view = $this;

			$func = function( $file, $template, & $local ) use ( $app, $view ) {
				if( $file !== false )
				{
					include $file;
				}
				else {
					return "Template '{$template}' not found.";
				}
			};
		}

		if( $level > $this->iterationLimit )
		{
			return "[iteration limit]";
		}

		if( ! is_array( $local ) )
		{
			$local = [];
		}

		$root = $level < 1;
		$back = $current_path;
		if( $root )
		{
			$this->template = $this->package->getTemplate($name);
			$path = $this->template->getPath();
		}
		else
		{
			if($name[0] === ".")
			{
				$name = strlen($current_path) ? ($current_path . $name) : substr($name, 1);
			}

			$path = $this->package->getTplPath($name);
			if(!$path !== false)
			{
				$dot = strrpos($name, ".");
				if( $dot !== false )
				{
					$current_path = substr($name, 0, $dot);
				}
			}
		}

		++ $level;

		// create global local array link
		if( !empty($this->items['__local__']) )
		{
			$local['__parent__'] =& $this->items['__local__'];
			unset( $this->items['__local__'] );
		}

		$local = new Prop($local);

		$parentTemplate = $local->getOr("__template__", null);
		$this->items['__local__'] =& $local;
		$this->items['__level__'] = $level;
		$this->items['__template__'] = $name;

		ob_start();
		$body = $func( $path, $name, $local );
		$tpl  = ob_get_contents();
		ob_end_clean();
		-- $level;

		// back parent local
		unset($this->items['__local__'], $this->items['__template__'], $this->items['__level__']);
		if( $level ) {
			$this->items['__level__'] = $level;
			if( $parentTemplate ) {
				$this->items['__template__'] = $parentTemplate;
			}
			if( !empty($local['__parent__']) ) {
				$this->items['__local__'] =& $local['__parent__'];
			}
		}

		if( !is_string($body) || !strlen($body) ) {
			$body = $tpl;
		}

		$current_path = $back;

		unset( $local, $back, $tpl );

		if( $root )
		{
			// replace plugin data (delay and short_tags) too, default YES
			$replace_plugin_content = $this->template->getOr("replace_plugin_content", true);
			$keys = $replace_plugin_content ? array_keys($this->plugin) : [];

			// replace delay variables, default YES
			if( $this->template->getOr("replace_delay", true) )
			{
				$depth = (int) $this->template->getOr("delay_depth", 1);
				$body = $this->replaceDelay( $body, $depth );
				if($replace_plugin_content)
				{
					foreach($keys as $key)
					{
						$this->plugin[$key]["content"] = $this->replaceDelay($this->plugin[$key]["content"], $depth);
					}
				}
			}

			// replace short tags, default NO
			if( $this->template->get("replace_short_tag") )
			{
				$depth = (int) $this->template->getOr("short_tag_depth", 1);
				$body = $this->replaceShortTag($body, false, $depth);
				if($replace_plugin_content)
				{
					foreach($keys as $key)
					{
						$this->plugin[$key]["content"] = $this->replaceShortTag($this->plugin[$key]["content"], false, $depth);
					}
				}
			}

			// dispatch render complete event
			// update body
			$event = new RenderCompleteEvent( $this->template, $body );
			EventManager::dispatch($event);
			$body = $event->getParam("body");

			$this->template = null;
		}

		return $body;
	}

	public function tplExists( $template )
	{
		return $this->package->getTplPath( $template ) !== false;
	}

	/**
	 * Get plugin data
	 *
	 * @param $name
	 * @param array $data
	 * @param bool $rawResult
	 * @return mixed
	 * @throws \Exception
	 */
	public function getPlugin( $name, array $data = [], $rawResult = false )
	{
		if( ! isset($this->plugins[$name]) )
		{
			return "Plugin '{$name}' not found.";
		}

		$plug = $this->plugins[$name];
		$class_name = $plug["class_name"];
		if( !is_array($data) )
		{
			$data = [$data];
		}

		/** @var Plugin $plugin */
		$plugin = new $class_name( $data, $this );

		if( !$plugin instanceof Plugin )
		{
			throw new \Exception("Plugin must be inherited of \\EApp\\Proto\\Plugin", 500);
		}

		if( $rawResult )
		{
			return $plugin->getContent();
		}

		$cache_type = $plugin->cacheType();
		$cache_view = $cache_type === "view";

		if( $cache_view || $cache_type === "plugin" )
		{
			$cache_name = Str::snake($name);
			$cache_data = $plugin->cacheData();
			$id = $name;
			if( isset($cache_data["id"]) )
			{
				$id = $cache_data["id"];
				unset($cache_data["id"]);
			}

			$cache = new Cache( $id, "plugin/" . $cache_name, $cache_data );
			if( $cache->ready() )
			{
				$content = $cache->getContentData();
			}
			else
			{
				$content = $plugin->getContent();
				$cache->write($content);
			}

			if($cache_view) {
				return $content;
			}
		}
		else
		{
			$content = $plugin->getContent();
		}

		$number = $this->plugin_index ++;
		$hash = md5(mt_rand( 1000, 100000 )) . "-" . time();
		$this->plugin[$number] = [
			"package"   => $plug["package_name"],
			"name"      => $name,
			"cache"     => $cache_type,
			"cacheData" => $plugin->cacheData(),
			"content"   => $content,
			"data"      => $data,
			"hash"      => $hash
		];

		return '{plugin:' . $number . ':' . $hash . '}';
	}

	public function registerShortPlugin( $name, $class_name )
	{
		if( !class_exists($class_name, true) )
		{
			App::Log("Plugin load error, class '{$class_name}' not found");
			return $this;
		}

		$ref = new \ReflectionClass($class_name);
		$short_interface = PluginShotable::class;

		if( in_array( $short_interface, $ref->getInterfaces()) )
		{
			App::Log("Plugin load error, class '{$class_name}' must be inherited of {$short_interface}");
			return $this;
		}

		$this->shortTags[$name] = $class_name;
		return $this;
	}

	public function registerShortPluginClosure( $name, \Closure $plugin )
	{
		$this->shortTags[$name] = $plugin;
		return $this;
	}

	public function replaceShortTag( $content, $fromPageData = false, $nestingLevel = 1 )
	{
		static $init = false;
		static $split;

		if( !$init )
		{
			$init = true;
			$split = static function( & $map, $cut )
			{
				$cut = preg_replace( '/=/', ' = ', $cut );
				$cut = preg_replace( '/\s+/', ' ', $cut );
				$cut = explode( " ", $cut );

				$isv = false;
				$txt = '';

				for( $i = 0, $ln = count($cut); $i < $ln; $i++ )
				{
					$v = $cut[$i];
					if( strlen($v) ) {
						if($v[0] === '{')
						{
							$isv = true;
							$txt = $v;
						}
						else if( $isv )
						{
							$txt .= ' ' . $v;
							if( strpos($v, '}') === strlen($v) - 1 )
							{
								$map[] = [ false, $txt ];
								$isv = false;
								$txt = '';
							}
						}
						else
						{
							$map[] = [ false, $v ];
						}
					}
				}

				if( $isv )
				{
					$map[] = [ false, $txt ];
				}
			};
		}

		if( $fromPageData )
		{
			if( !isset( $this->items[$content] ) )
			{
				return "";
			}
			else {
				$content = $this->items[$content];
			}
		}

		if( !is_string($content) || ($strLen = strlen($content)) < 1 )
		{
			return $content;
		}
		if( strpos($content, '{{') === false )
		{
			return $this->_nsTag($content);
		}

		$start = 0;
		$get = "";

		if( !is_int($nestingLevel) ) {
			$nestingLevel = (int) $nestingLevel;
		}

		if( $nestingLevel > 10 ) {
			$nestingLevel = 10;
		}

		for(;;)
		{
			$pos = strpos( $content, '{{', $start );
			if( $pos === false ) {
				break;
			}

			if( $pos > $start ) {
				$get .= $this->_nsTag(substr( $content, $start, $pos - $start ));
				$start = $pos;
			}

			// {{ FuncName `name` `valueName` `item` = `title it's \`escape\` }}` }}
			// convert to PHP
			// FuncName( [ "name", "valueName", "item" => "title it's `escape` }}" ] )

			$map = [];
			$wor = $pos + 2;

			for(;;) {
				$end = strpos( $content, '}}', $wor );
				if( $end === false ) {
					break 2;
				}

				$qut = strpos( $content, "`", $wor );
				if( $qut === false || $qut > $end ) {

					$cut = $end === $wor ? "" : trim( substr( $content, $wor, $end - $wor ) );
					if( strlen($cut) ) {
						$split( $map, $cut );
					}

					$end = $end + 2;
					break;
				}
				else {

					if( $wor < $qut ) {
						$cut = trim( substr( $content, $wor, $qut - $wor ) );
						if( strlen($cut) ) {
							$split( $map, $cut );
						}
					}

					$q1 = $qut+1;
					for(;;) {
						$q2 = strpos( $content, "`", $q1 );
						if( $q2 === false ) {
							break 3;
						}

						$n = 0;
						for( ; $q2+1<$strLen ; $q2++ ) {
							if( $content[$q2+1] === "`" ) $n++;
							else break;
						}

						if( $n%2 === 0 ) {
							$cut = substr( $content, $qut+1, $q2-$qut-1 );
							$cut = str_replace( "``", "`", $cut );
							$cut = $this->_nsTag( $cut );

							$map[] = [ true, ($qut+1 < $q2 ?  $cut : "") ];
							$wor = $q2+1;
							break;
						}
						else {
							$q1 = $q2+1;
						}
					}
				}
			}

			$mln = count( $map );

			// short tag is empty
			// or first name is escape quote
			// or register short name callback not exists

			if( !$mln || $map[0][0] || !$this->_hasShortTag( $map[0][1] ) )
			{
				$get .= substr( $content, $start, $end - $start );
			}

			// convert array to assoc
			// call short tag method or function

			else {
				$name = $map[0][1];
				$args = [];

				// parse arguments (convert)
				for( $i = 1; $i<$mln; $i++ ) {

					$key = $map[$i][1];
					if( $key === "=" && $i === 1 ) {
						$i = 0;
						$key = $name;
					}
					else if( $key[0] == "{" && $key[strlen($key)-1] == "}" ) {
						$key = $this->_rsVar([ 1 => trim( $key, "{}") ]);
					}

					if( $i+1<$mln && !$map[$i+1][0] && $map[$i+1][1] === "=" ) {
						$i += 2;

						if( $i<$mln ) {
							$val = $map[$i][1];

							if( $val[0] == "{" && $val[strlen($val)-1] == "}" ) {
								$val = $this->_rsVar([ 1 => trim( $val, "{}") ], true);
							}
							else if( !$map[$i][0] ) {
								if( $val === "true" ) {
									$val = true;
								}
								else if( $val === "false" ) {
									$val = false;
								}
								else if( $val === "null" ) {
									$val = null;
								}
							}

							if( !$map[$i][0] && is_numeric($val) ) {
								$val = strpos( $val, "." ) === false ? intval($val) : floatval($val);
							}
						}
						else {
							$val = "";
						}

						$args[$key] = $val;
					}
					else {
						$args[] = $key;
					}
				}

				$class_name = $this->_getShortTag($name);

				// call function

				if( $class_name instanceof \Closure )
				{
					$get .= $class_name( $name, $args, $this );
				}

				// create new class instance and invoke getContent()

				else
				{
					/** @var \EApp\Plugin\Interfaces\PluginShotable $plugin */
					$plugin = new $class_name( $args, $this );
					$plugin->toShortTag();
					$get .= $plugin->getContent();
					unset($plugin);
				}
			}

			// next line

			$start = $end;
		}

		if( $start === 0 )
		{
			return $content;
		}

		if( $start+1 < $strLen )
		{
			$get .= $this->_nsTag(substr( $content, $start ));
		}

		if( $nestingLevel > 1 )
		{
			return $this->replaceShortTag( $get, false, $nestingLevel - 1 );
		}

		return $get;
	}

	public function replaceDelay( $html, $nestingLevel = 1 )
	{
		if( !is_int($nestingLevel) )
		{
			$nestingLevel = (int) $nestingLevel;
		}

		if( $nestingLevel > 10 )
		{
			$nestingLevel = 10;
		}
		else if( $nestingLevel < 1 )
		{
			$nestingLevel = 1;
		}

		// max level = 3
		for( ; $nestingLevel > 0; $nestingLevel-- )
		{
			if( strpos( $html, '{item:' ) === false ) {
				break;
			}
			$html = preg_replace_callback( '/\{item:(.*?):(.*?)\}/', [$this, "_replaceDelay"], $html );
		}

		return $html;
	}

	public function eachPluginData( $rawData, $callBack, $nestingLevel = 1 )
	{
		if( ! is_callable( $callBack ) )
		{
			$callBack = function( $pluginData )
			{
				return '[no callable, ' . $pluginData["name"] . ":" . $pluginData["driver"] . ']';
			};
		}

		$replace = function( $m ) use ( $callBack )
		{
			$id = (int) $m[1];
			if( isset( $this->plugin[$id] ) && $m[2] === $this->plugin[$id]["hash"] ) {
				return $callBack( $this->plugin[$id] );
			}
			else {
				return $m[0];
			}

		};

		if( ! is_int($nestingLevel) )
		{
			$nestingLevel = (int) $nestingLevel;
		}

		if( $nestingLevel > 10 )
		{
			$nestingLevel = 10;
		}
		else if( $nestingLevel < 1 )
		{
			$nestingLevel = 1;
		}

		// max level = 3
		for( ; $nestingLevel > 0; $nestingLevel-- )
		{
			if( strpos( $rawData, '{plugin:' ) === false )
			{
				break;
			}

			$rawData = preg_replace_callback( '/\{plugin:(\d+):(.*?)\}/', $replace, $rawData );
		}

		return $rawData;
	}

	// private

	private function _hasShortTag($name)
	{
		return isset($this->shortTags[$name]);
	}

	private function _getShortTag($name)
	{
		return isset($this->shortTags[$name]) ? $this->shortTags[$name] : null;
	}

	private function _nsTag( $str )
	{
		if( strlen($str) && strpos($str, "{") !== false )
		{
			$str = preg_replace_callback( '/\{(.*?)\}/', [$this,"_rsVar"], $str );
		}
		return $str;
	}

	private function _replaceDelay( $map )
	{
		if( ! isset( $this->getDelay[$map[1]] ) || $this->getDelay[$map[1]] !== $map[2] )
		{
			return $map[0];
		}
		else
		{
			return $this->_rsVar( $map );
		}
	}

	private function _rsVar( $map, $raw = false )
	{
		$key = trim($map[1]);
		if( $key[0] !== "\$" ) {
			return isset( $map[0] ) ? $map[0] : "";
		}

		$key = substr($key, 1);
		$key = preg_replace( '/\s+/', ' ', $key );
		$mdf = false;

		$pos = strpos( $key, ' ' );
		if( $pos )
		{
			$mdf = explode( ' ', substr( $key, $pos ) );
			$key = substr( $key, 0, $pos );
		}

		if( strpos( $key, "->" ) !== false ) {

			$key = explode( "->", $key );
			if( !isset( $this->items[$key[0]]) )
			{
				return "";
			}

			$len = count( $key );
			if( $len == 2 )
			{
				$found = isset( $this->items[$key[0]][$key[1]] ) ? $this->items[$key[0]][$key[1]] : '';
			}
			else if( $len == 3 )
			{
				$found = isset( $this->items[$key[0]][$key[1]][$key[2]] ) ? $this->items[$key[0]][$key[1]][$key[2]] : '';
			}
			else {
				$found =& $this->items[$key[0]];
				for( $i = 1; $i < $len; $i++ )
				{
					if( !isset( $found[$key[$i]] ) )
					{
						return "";
					}
					else if( is_array($found) )
					{
						$saved =& $found[$key[$i]];
						unset( $found );
						$found =& $saved;
						unset( $saved );
					}
					else
					{
						$found = $found[$key[$i]];
					}
				}
			}
		}
		else {
			$found = $this->get( $key );
		}

		if( $mdf !== false )
		{
			foreach( $mdf as $m )
			{
				$found = $this->_mdf( $m, $found );
			}
		}

		if( $raw )
		{
			return $found;
		}

		if( is_object($found) && method_exists($found, "__toString") || is_scalar($found) )
		{
			return (string) $found;
		}

		return '[]';
	}

	private function _mdf( $name, $value )
	{
		$name = strtolower($name);

		if( $name === 'length' )
		{
			if( is_array($value) )
			{
				return count($value);
			}
			else
			{
				return mb_strlen( (string) $value, $this->charset );
			}
		}

		if( $name === 'debug' )
		{
			return '<pre>' . print_r( $value, true ) . '</pre>';
		}

		if( is_array($value) || is_object($value) )
		{
			return $value;
		}

		$value = (string) $value;
		switch( $name )
		{
			case 'trim':   return trim( $value );
			case 'ltrim':  return ltrim( $value );
			case 'rtrim':  return rtrim( $value );
			case 'escape': return htmlspecialchars( $value, ENT_COMPAT, $this->charset );
			case 'entity': return htmlentities( $value, null, $this->charset );
			case 'strip':  return strip_tags( $value );
			case 'lower':  return mb_strtolower( $value, $this->charset );
			case 'upper':  return mb_strtoupper( $value, $this->charset );
			case 'title':  return mb_convert_case( $value, MB_CASE_TITLE, $this->charset );
			case 'type':   return gettype( $value );
		}

		if( preg_match( '/^cut:(\d+)(?::(\d+))?/', $name, $m ) )
		{
			if( $m[2] )
			{
				return mb_substr( $value, (int) $m[1], (int) $m[2], $this->charset );
			}
			else
			{
				return mb_substr( $value, 0, (int) $m[1], $this->charset );
			}
		}

		return $value;
	}

	private function _ver( $file )
	{
		static $init = false;
		static $write = false, $data = [];

		if( !file_exists($file) )
		{
			return '';
		}

		$time = @filemtime( $file );
		if( !$time )
		{
			return '';
		}

		if( !$init )
		{
			$init  = true;
			$cache = new Cache( "assets_time", ".system" );
			if( $cache->ready() ) {
				$data = $cache->getContentData();
			}

			EventManager::listen("onSystem", function( SystemEvent $event ) use ( & $write, & $data, $cache ) {
				if( $write && $event instanceof ShutdownEvent )
				{
					$cache->write( $data );
				}
			});
		}

		if( !isset( $data[$file] ) )
		{
			$data[$file] = [ 1, $time ];
			$write = true;
		}
		else if( $data[$file][1] < $time )
		{
			$data[$file][0] ++;
			$data[$file][1] = $time;
			$write = true;
		}

		return "?v=" . $data[$file][0];
	}
}
