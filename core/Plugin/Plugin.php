<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 0:52
 */

namespace EApp\Plugin;

use EApp\Cache;
use EApp\View\View;
use EApp\Support\Str;
use EApp\Traits\GetTrait;
use EApp\Traits\CompareTrait;
use EApp\Plugin\Interfaces\PluginPrepareProperties;

abstract class Plugin
{
	use GetTrait;
	use CompareTrait;

	protected $items = [];

	/**
	 * @values nocache | data | view | plugin | page
	 * @var string
	 */
	protected $cache_type = "nocache";

	protected $cache_data = [];

	protected $view;

	protected $custom_display = false;

	protected $plugin_data = [];

	public function __construct( $data, View $view )
	{
		$this->view = $view;

		if( ! is_array( $data ) )
		{
			return;
		}

		if( isset( $data["cache"] ) )
		{
			if( is_array($data["cache"]) )
			{
				$cache = $data["cache"];
				$cache_type = $cache["type"] ?? "plugin";
				$this->cache_data = $cache;
				unset($cache, $this->cache_data["type"]);
			}
			else
			{
				$cache_type = $data["cache"];
			}

			$cache_type = strtolower( trim( $cache_type ) );
			if( $cache_type === "page" || $cache_type === "plugin" || $cache_type === "view" || $cache_type === "data" )
			{
				$this->cache_type = $cache_type;
			}

			unset( $data["cache"] );
		}

		if( $this instanceof PluginPrepareProperties )
		{
			$cache = $this->prepareProperties( $data );
			if( is_array( $cache ) )
			{
				foreach( $cache as $key => $value )
				{
					$this->setCacheValue( $key, $value );
				}
			}
		}
		else
		{
			$this->items = $data;
		}
	}

	/**
	 * Load plugin data
	 * @return $this
	 */
	public function load()
	{
		if( $this->cacheType() === "data" )
		{
			$ref = new \ReflectionClass($this);
			$cache_name = Str::snake($ref->getShortName());
			$cache_data = $this->cacheData();

			if( isset($cache_data["id"]) )
			{
				$id = $cache_data["id"];
				unset($cache_data["id"]);
			}
			else
			{
				$id = $cache_data;
			}

			$cache = new Cache( $id, "plugin/" . $cache_name, $cache_data );
			if( $cache->ready() )
			{
				$this->plugin_data = $cache->import();
			}
			else
			{
				$this->loadPluginData();
				$cache->export($this->plugin_data);
			}
		}
		else
		{
			$this->loadPluginData();
		}

		return $this;
	}

	/**
	 * GetTrait cache type.
	 * Valid values: nocache, data, view, plugin
	 *
	 * @return string
	 */
	public function cacheType(): string
	{
		return $this->cache_type;
	}

	public function cacheData(): array
	{
		return $this->cache_data;
	}

	/**
	 * @return bool
	 */
	public function isCustomDisplay(): bool
	{
		return $this->custom_display;
	}

	/**
	 * @return array
	 */
	public function getPluginData(): array
	{
		return $this->plugin_data;
	}

	public function getTplName(): string
	{
		$tpl = $this->get("tpl");

		if(!$tpl)
		{
			$tpl = str_replace( "\\", '.', get_class($this) );
			$tpl = strtolower( trim($tpl, ".") );
			if( substr( $tpl, 0, 7 ) == 'plugin.' )
			{
				$tpl = substr( $tpl, 7 );
			}
			$this->items["tpl"] = $tpl;
		}

		return $tpl;
	}

	public function getRenderContent( View $view )
	{
		if( $this->isCustomDisplay() )
		{
			throw new \RuntimeException("You must overloaded the " . __METHOD__ . " method for the CUSTOM_DISPLAY mode");
		}
		else
		{
			return $view->getTpl( $this->getTplName(), $this->plugin_data );
		}
	}

	// protected

	abstract protected function loadPluginData();

	protected function setCacheValue( $name, $value )
	{
		$name = strtolower(trim($name));
		if( $name === "life" && ! is_int($value) )
		{
			return;
		}

		$this->cache_data[$name] = $value;
	}
}