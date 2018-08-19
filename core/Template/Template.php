<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\Template;

use EApp\Cache;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Json;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\GetIdentifier;

class Template
{
	use Get;
	use GetIdentifier;

	protected $name;

	protected $title = "";

	protected $items = [];

	protected $package;

	public function __construct( Package $package, $name, $cached = true )
	{
		$this->name = (string) $name;
		$this->package = $package;

		if( !$cached )
		{
			$this->load();
		}
		else {
			$cache = new Cache( $name, 'template/' . $package->getId() );
			if( $cache->ready() )
			{
				$this->items = $cache->import();
			}
			else {
				$this->load();
				$cache->export($this->items);
			}
		}
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		return $this->package->getModule();
	}

	/**
	 * @return \EApp\Template\Package
	 */
	public function getPackage()
	{
		return $this->package;
	}

	public function getPath()
	{
		$path = $this->package->getTplPath($this->name);
		if($path === false)
		{
			throw new NotFoundException("Template file not found for selected package");
		}

		return $path;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->get("title");
	}

	private function load()
	{
		$row = \DB::table("template_data")
			->where('package_id', $this->package->getId())
			->where("name", $this->name)
			->first();

		if( !$row ) {
			throw new NotFoundException("Template '{$this->name}' not found for selected package");
		}

		$this->id = (int) $row->id;

		$this->items = Json::parse($row->properties, true);
		$this->items["id"] = $this->id;
		$this->items["name"] = $this->name;
		$this->items["title"] = $row->title;
	}
}