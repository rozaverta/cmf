<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace EApp\View;

use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\ModuleComponentInterface;
use EApp\Traits\GetTrait;
use EApp\Traits\GetIdentifierTrait;
use EApp\Traits\GetModuleComponentTrait;
use EApp\View\Scheme\TemplatesSchemeDesigner;

class Template implements ModuleComponentInterface
{
	use GetTrait;
	use GetIdentifierTrait;
	use GetModuleComponentTrait;

	protected $name;

	protected $title;

	protected $items = [];

	/**
	 * @var TemplatesSchemeDesigner
	 */
	protected $scheme;

	/**
	 * @var Package
	 */
	protected $package;

	public function __construct( Package $package, TemplatesSchemeDesigner $scheme )
	{
		if($package->getId() !== $scheme->package_id)
		{
			throw new \InvalidArgumentException("Template ID and package do not match");
		}

		$this->name = $scheme->name;
		$this->title = $scheme->title;
		$this->items = $scheme->properties;
		$this->scheme = $scheme;
		$this->package = $package;
		$this->setModule($package->getModule());
	}

	/**
	 * @return \EApp\View\Package
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * @return TemplatesSchemeDesigner
	 */
	public function getScheme(): TemplatesSchemeDesigner
	{
		return $this->scheme;
	}

	public function getPath()
	{
		$path = $this
			->package
			->getTplPath($this->name);

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
		return $this->title;
	}
}