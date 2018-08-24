<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\View;

use EApp\Cache;
use EApp\Component\Module;
use EApp\Exceptions\NotFoundException;
use Eapp\Helper;
use EApp\Interfaces\ModuleComponentInterface;
use EApp\ModuleCore;
use EApp\Traits\CacheIdentifierInstanceTrait;
use EApp\Traits\GetIdentifierTrait;
use EApp\Traits\GetModuleComponentTrait;
use EApp\View\Scheme\TemplatePackagesSchemeDesigner;
use EApp\View\Scheme\TemplatesSchemeDesigner;

class Package implements ModuleComponentInterface
{
	use GetIdentifierTrait;
	use GetModuleComponentTrait;
	use CacheIdentifierInstanceTrait;

	protected $name;

	protected $title;

	protected $version;

	protected $readme;

	protected $license;

	protected $assets;

	protected $assets_path;

	protected $view_path;

	protected $func_file_path;

	protected $is_func;

	protected $tpl = [];

	public function __construct( int $id )
	{
		$row = \DB
			::table("template_packages")
				->whereId($id)
				->setResultClass(TemplatePackagesSchemeDesigner::class)
				->first();

		/** @var TemplatePackagesSchemeDesigner $row */
		if( ! $row )
		{
			throw new NotFoundException("Package '{$id}' not found");
		}

		$view_path = APP_DIR . 'view' . DIRECTORY_SEPARATOR . $row->name . DIRECTORY_SEPARATOR;
		$func_file_path = $view_path . "func_required.inc.php";

		$data = $row->toArray();
		$data["assets"] = ASSETS_PATH . $row->name . "/";
		$data["assets_path"] = ASSETS_DIR . $row->name . DIRECTORY_SEPARATOR;
		$data["view_path"] = $view_path;
		$data["func_file_path"] = $func_file_path;
		$data["is_func"] = file_exists($func_file_path);

		$this->fill($data);
	}

	/**
	 * @param string $name
	 * @return Template
	 */
	public function getTemplate($name): Template
	{
		static $load = [];

		if( $this->isLoadedFromCache() )
		{
			if( ! isset($load[$name]) )
			{
				$cache = new Cache( $name, 'template/' . $this->getId() );
				if( $cache->ready() )
				{
					$scheme = $cache->import();
				}
				else
				{
					$scheme = $this->getTemplateScheme($name);
					$cache->export($scheme);
				}

				$load[$name] = new Template($this, $scheme);
			}

			return $load[$name];
		}
		else
		{
			return new Template($this, $this->getTemplateScheme($name));
		}
	}

	public function getTemplateScheme($name): TemplatesSchemeDesigner
	{
		$row = \DB
			::table("templates")
				->where('package_id', $this->getId())
				->where("name", $name)
				->setResultClass(TemplatesSchemeDesigner::class)
				->first();

		if( !$row )
		{
			throw new NotFoundException("The '{$name}' template not found for the '" . $this->getName() . "' package");
		}

		return $row;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * @return string
	 */
	public function getLicense(): string
	{
		return $this->license;
	}

	/**
	 * @return string
	 */
	public function getReadme(): string
	{
		return $this->readme;
	}

	/**
	 * @return string
	 */
	public function getAssets(): string
	{
		return $this->assets;
	}

	/**
	 * @return string
	 */
	public function getAssetsPath(): string
	{
		return $this->assets_path;
	}

	/**
	 * @return string
	 */
	public function getFuncFilePath(): string
	{
		return $this->func_file_path;
	}

	/**
	 * @return string
	 */
	public function getViewPath(): string
	{
		return $this->view_path;
	}

	/**
	 * @return bool
	 */
	public function isFunc(): bool
	{
		return $this->is_func;
	}

	/**
	 * Include function file.
	 *
	 * @param View $view
	 * @return $this
	 */
	public function includeFunc( View $view)
	{
		if( $this->isFunc() )
		{
			Helper::includeFile( $this->getFuncFilePath(), ["view" => $view], false, true );
		}
		return $this;
	}

	public function getTplPath( $name, $exists = true )
	{
		if( !$exists )
		{
			return $this->getViewPath() . str_replace('.', DIRECTORY_SEPARATOR, $name) . ".php";
		}

		if( !isset($this->tpl[$name]) )
		{
			$file = $this->getViewPath() . str_replace('.', DIRECTORY_SEPARATOR, $name) . ".php";
			$this->tpl[$name] = file_exists($file) ? $file : false;
		}

		return $this->tpl[$name];
	}

	private function fill( array $data )
	{
		$this->id = $data["id"];
		$this->name = $data["name"];
		$this->title = $data["title"];
		$this->version = $data["version"];
		$this->readme = $data["readme"];
		$this->license = $data["license"];
		$this->assets = $data["assets"];
		$this->assets_path = $data["assets_path"];
		$this->view_path = $data["view_path"];
		$this->func_file_path = $data["func_file_path"];
		$this->is_func = $data["is_func"];
		$this->setModule(
			$this->isLoadedFromCache()
				? Module::cache($data["module_id"])
				: ($data["module_id"] > 0 ? new Module($data["module_id"]) : new ModuleCore())
		);
	}

	protected static function createCache( int $id ): Cache
	{
		return new Cache($id, 'template/package');
	}

	protected function importCacheData( $data )
	{
		$this->fill($data);
	}

	protected function exportCacheData()
	{
		return [
			"id" => $this->id,
			"module_id" => $this->getModuleId(),
			"name" => $this->name,
			"title" => $this->title,
			"version" => $this->version,
			"readme" => $this->readme,
			"license" => $this->license,
			"assets" => $this->assets,
			"assets_path" => $this->assets_path,
			"view_path" => $this->view_path,
			"func_file_path" => $this->func_file_path,
			"is_func" => $this->is_func
		];
	}
}