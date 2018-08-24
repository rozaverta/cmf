<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 11:16
 */

namespace EApp\Component\Driver;

use EApp\Component\Module;
use EApp\Event\EventManager;
use EApp\Helper;
use EApp\Prop;
use EApp\Interfaces\Loggable;
use EApp\Traits\{
	GetTrait, GetModuleComponentTrait, LoggableTrait, SetTrait, Write
};
use EApp\Interfaces\SystemDriverInterface;
use EApp\Text;

class ConfigFile implements SystemDriverInterface, Loggable
{
	use LoggableTrait;
	use GetModuleComponentTrait;
	use Write;
	use SetTrait;
	use GetTrait;

	protected $items = [];

	protected $name;

	protected $file_name;

	protected $file_basedir;

	protected $ready = null;

	public function __construct( string $name, Module $module )
	{
		$this->setModule($module);

		if( preg_match('/\.php$/i', $name) )
		{
			$name = substr($name, 0, strlen($name) - 4);
		}

		$this->name = $name;
		$this->file_basedir = APP_DIR . "config" . DIRECTORY_SEPARATOR;
		if($module->getId() > 0)
		{
			$this->file_basedir .= $module->getKey() . DIRECTORY_SEPARATOR;
		}
		$this->file_name = $this->file_basedir . $this->name . ".php";
	}

	/**
	 * @return bool
	 */
	public function reload()
	{
		if( $this->fileExists() )
		{
			$this->items = Helper::includeImport($this->file_name);
			return true;
		}
		else
		{
			$this->items = [];
			return false;
		}
	}

	/**
	 * @param array $items
	 * @param bool $update
	 * @return $this
	 */
	public function merge( array $items, $update = false )
	{
		if( ! count($this->items) )
		{
			$this->items = $items;
		}
		else if( $update )
		{
			$this->items = array_merge($this->items, $items);
		}
		else
		{
			foreach($items as $key => $value)
			{
				if( ! $this->offsetExists($key) )
				{
					$this->items[$key] = $value;
				}
			}
		}

		return $this;
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
	public function getFileName(): string
	{
		return $this->file_name;
	}

	/**
	 * @return string
	 */
	public function getFileBasedir(): string
	{
		return $this->file_basedir;
	}

	/**
	 * @return bool
	 */
	public function fileExists(): bool
	{
		return file_exists($this->file_name) && is_file($this->file_name);
	}

	/**
	 * @return bool
	 */
	public function write(): bool
	{
		// trigger
		$event = new Events\ConfigFileDriverEvent($this);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher->dispatch($event);
		$write = false;

		if( $event->hasAborted() )
		{
			$this->addLogDebug(Text::createInstance("Aborted the write action for the %s config file of the %s module", $this->name, $this->getModule()->getName()));
		}
		else if(
			$this->makeDir($this->file_basedir) &&
			$this->writeFileContent($this->file_name, $this->getAll(), false)
		)
		{
			$write = true;
		}

		$dispatcher->complete(new Prop([
			"write" => $write,
			"aborted" => ! $write && $event->hasAborted()
		]));

		return $write;
	}
}