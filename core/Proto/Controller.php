<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace EApp\Proto;

use EApp\Prop;
use EApp\Component\Module;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\GetIdentifier;
use EApp\Support\Traits\LoggableTrait;
use EApp\Support\Traits\Get;

abstract class Controller implements Loggable
{
	use LoggableTrait;
	use Get;
	use GetIdentifier;

	protected $items = [];
	protected $name = false;
	protected $cacheable = false;

	/**
	 * @var \EApp\Prop
	 */
	protected $properties;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	/**
	 * @var array | object
	 */
	protected $pageData = [];

	abstract public function ready();

	public function __construct( Module $module, array $data = [] )
	{
		$name = get_class($this);
		if( strpos($name, $module->get('name_space')) !== 0 )
		{
			throw new \Exception("Invalid current module");
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

		$this->module = $module;
		$this->properties = new Prop();
		$this->items = $data;
	}

	public function name()
	{
		if( !$this->name )
		{
			$name = $this->module->get('key');
			if( preg_match('/Controller\\\\(.*?)$/', get_class($this), $e ) )
			{
				$name .= '::' . preg_replace_callback( '/[A-Z]/', static function( $m ) { return '_' . lcfirst( $m[0] ); }, lcfirst( $e[1] ) );
				$name  = str_replace( '\\', ':', $name );
			}
			$this->name = strtolower( $name );
		}

		return $this->name;
	}

	public function cacheable()
	{
		return $this->cacheable;
	}

	public function module()
	{
		return $this->module;
	}

	// for override
	public function complete() {}

	/**
	 * @return array
	 */
	public function properties()
	{
		return $this->properties->getAll();
	}

	public function pageData()
	{
		return $this->pageData;
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
			$name = $name->get("key");
		}

		return $this->module->support($name) && method_exists($this, $method);
	}
}