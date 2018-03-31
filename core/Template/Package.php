<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\Template;

use EApp\App;
use EApp\Cache;
use EApp\Component\Module;
use EApp\Support\Traits\Get;

class Package
{
	use Get;

	protected $items = [];
	private $tpl = [];

	public function __construct( $id, $cached = true )
	{
		$id = (int) $id;

		if( !$cached )
		{
			$this->load($id);
		}
		else {
			$cache = new Cache( $id, 'template/package' );
			if( $cache->ready() )
			{
				$this->items = $cache->getContentData();
			}
			else {
				$this->load($id);
				$cache->write($this->items);
			}
		}
	}

	public function getData()
	{
		return $this->items;
	}

	/**
	 * @return Module | null
	 */
	public function getModule()
	{
		$module_id = $this->get("module_id");
		return $module_id > 0 ? Module::cache($module_id) : null;
	}

	/**
	 * Include function file.
	 *
	 * @return $this
	 */
	public function func()
	{
		$this->get('func') && \E\IncludeFile( $this->get('func_path'), ["view" => App::View()], false, true );
		return $this;
	}

	public function getTplPath( $name, $exists = true )
	{
		if( !$exists )
		{
			return $this->items['view_path'] . str_replace('.', DIRECTORY_SEPARATOR, $name) . ".php";
		}

		if( !isset($this->tpl[$name]) )
		{
			$file = $this->items['view_path'] . str_replace('.', DIRECTORY_SEPARATOR, $name) . ".php";
			$this->tpl[$name] = file_exists($file) ? $file : false;
		}

		return $this->tpl[$name];
	}

	private function load( $id )
	{
		$row = \DB::table("template_packages")
			->whereId($id)
			->first();

		if( !$row ) {
			throw new \InvalidArgumentException("Package '{$id}' not found");
		}

		$view_path = APP_DIR . 'view' . DIRECTORY_SEPARATOR . $row->name . DIRECTORY_SEPARATOR;
		$func_path = $view_path . "func_required.inc.php";

		$this->items =
			[
				"id" => (int) $row->id,
				"module_id" => (int) $row->module_id,
				"assets" => ASSETS_PATH . $row->name . "/",
				"assets_path" => ASSETS_DIR . $row->name . DIRECTORY_SEPARATOR,
				"view_path" => $view_path,
				"func_path" => $func_path,
				"func" => file_exists($func_path)
			];
	}
}