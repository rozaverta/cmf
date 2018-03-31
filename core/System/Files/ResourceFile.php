<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.01.2018
 * Time: 5:13
 */

namespace EApp\System\Files;

use EApp\Support\Exceptions\FileReadyException;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Exceptions\ReadyException;
use EApp\Support\Json;
use EApp\Support\Traits\Get;

class ResourceFile
{
	use Get;

	protected $file;
	protected $ready = false;
	protected $items = [];
	protected $type  = 'unknown';
	protected $name  = '';
	protected $path  = '';

	public function __construct( $file )
	{
		if( $file[0] === '@' )
		{
			$file = \E\Path($file);
		}

		$file = realpath($file);
		$dot = strrpos($file, '.');
		if( $dot === false )
		{
			$dot = strlen($file);
			$file .= '.json';
		}
		else if( strtolower(substr($file, $dot)) !== '.json' )
		{
			throw new \InvalidArgumentException("The resource must be a json data file.");
		}

		if( ! is_file($file) )
		{
			throw new NotFoundException("The resource file not found.");
		}

		if( DIRECTORY_SEPARATOR !== '/' && strpos($file, '/') !== false )
		{
			$file = str_replace('/', DIRECTORY_SEPARATOR, $file );
		}

		$end = strrpos($file, DIRECTORY_SEPARATOR);
		$this->file = $file;
		$this->path = $end === false ? "" : substr($file, 0, $end + 1);
		$this->name = $end === false ? substr($file, 0, $dot) : substr($file, $end + 1, $dot - $end - 1);
	}

	public function ready()
	{
		if( $this->ready )
		{
			return $this;
		}

		$data = @ file_get_contents($this->file);
		if( !$data )
		{
			throw new FileReadyException("Cannot ready resource '{$this->name}'");
		}

		try {
			$data = Json::parse($data, true);
			if( ! is_array($data) )
			{
				throw new \InvalidArgumentException();
			}
		}
		catch( \InvalidArgumentException $e ) {
			throw new ReadyException("Cannot ready resource '{$this->name}', json parser error " . $e->getCode());
		}

		if( isset($data['type']) && is_string($data['type']) )
		{
			$this->type = $data['type'];
		}

		$this->ready = true;
		$this->items = $data;

		return $this;
	}

	public function getType()
	{
		return $this->ready()->type;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getFile()
	{
		return $this->file;
	}
}