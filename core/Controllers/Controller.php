<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace EApp\Controllers;

use EApp\Prop;
use EApp\Module\Module;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetIdentifierTrait;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Traits\GetTrait;
use EApp\Interfaces\ModuleComponentInterface;

abstract class Controller implements Loggable, ModuleComponentInterface
{
	use LoggableTrait;
	use GetTrait;
	use GetIdentifierTrait;
	use GetModuleComponentTrait;

	protected $items = [];

	protected $name = false;

	protected $cacheable = false;

	/**
	 * @var \EApp\Prop
	 */
	protected $properties;

	/**
	 * @var array
	 */
	protected $page_data = [];

	public function __construct( Module $module, array $data = [] )
	{
		$name = get_class($this);
		if( strpos($name, $module->getNamespace()) !== 0 )
		{
			throw new \InvalidArgumentException("Invalid current module");
		}

		if( isset($data['id']) )
		{
			$this->id = $data['id'];
		}

		if( isset($data['cacheable']) )
		{
			$this->cacheable = (bool) $data['cacheable'];
		}

		unset($data['id'], $data['cacheable']);

		$this->setModule($module);
		$this->items = $data;
		$this->properties = new Prop();
	}

	abstract public function ready();

	/**
	 * Get controller name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		if( !$this->name )
		{
			$name = $this->getModule()->getKey();
			if( preg_match('/Controllers\\\\(.*?)$/', static::class, $e ) )
			{
				$name .= '::' . preg_replace_callback( '/[A-Z]/', static function( $m ) { return '_' . lcfirst( $m[0] ); }, lcfirst( $e[1] ) );
				$name  = str_replace( '\\', ':', $name );
			}
			$this->name = strtolower( $name );
		}

		return $this->name;
	}

	public function isCacheable(): bool
	{
		return $this->cacheable;
	}

	// for override
	public function complete() {}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getProperty( string $name, $default = false )
	{
		return $this->properties->getOr($name, $default);
	}

	/**
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties->getAll();
	}

	/**
	 * @return array
	 */
	public function getPageData(): array
	{
		return $this->page_data;
	}

	/**
	 * Check support method for other module
	 *
	 * @param string | Module $name
	 * @param string $method
	 * @return bool
	 */
	public function supportPortalMethod( $name, $method )
	{
		if( $name instanceof Module )
		{
			$name = $name->getKey();
		}

		return $this->module->support($name) && method_exists($this, $method);
	}
}