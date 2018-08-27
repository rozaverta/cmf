<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:28
 */

namespace EApp\Schemes;

use EApp\Database\Schema\SchemeDesigner;

class ContextSchemeDesigner extends SchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $comment;

	/**
	 * @var string
	 */
	public $host;

	/**
	 * @var int
	 */
	public $host_port;

	/**
	 * @var string
	 */
	public $host_scheme;

	/**
	 * @var string
	 */
	public $path;

	/**
	 * @var array
	 */
	public $query;

	/**
	 * @var bool
	 */
	public $is_default;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->is_default = $this->is_default > 0;
		$this->host_port = (int) $this->host_port;

		$q = (string) $this->query;
		$this->query = [];

		if( strlen($q) )
		{
			@ parse_str($q, $this->query);
		}
	}

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return strlen($this->host) > 0 && filter_var($this->host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
	}

	/**
	 * @return bool
	 */
	public function isQuery(): bool
	{
		return count($this->query) > 0;
	}

	/**
	 * @return bool
	 */
	public function isPath(): bool
	{
		return strlen($this->path) > 0;
	}
}