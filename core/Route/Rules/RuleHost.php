<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:01
 */

namespace EApp\Route\Rules;

use EApp\Route\Interfaces\RuleInterface;

class RuleHost implements RuleInterface
{
	private $host;

	private $protocol;

	private $port;

	public function __construct( string $host, string $protocol = "", int $port = 0 )
	{
		$this->host = $host;
		$this->protocol = $protocol;
		$this->port = $port;
	}

	public static function __set_state($data)
	{
		return new static($data["host"], $data["protocol"] ?? "", $data["port"] ?? 0);
	}

	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getProtocol(): string
	{
		return $this->protocol;
	}

	/**
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port === 0 ? 80 : $this->port;
	}

	public function match( string $value, & $match = null ): bool
	{
		$data = parse_url($value);
		return is_array($data)
			&& ( ! $this->protocol || $this->protocol === $data["scheme"] )
			&& $this->host === $data["host"]
			&& ( $this->port === 0 || $this->port === intval($data["port"] ?? 80) );
	}
}