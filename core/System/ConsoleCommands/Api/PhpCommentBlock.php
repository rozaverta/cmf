<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.07.2018
 * Time: 23:21
 */

namespace EApp\System\ConsoleCommands\Api;

class PhpCommentBlock
{
	protected $name = null;

	protected $lines;

	public function __construct( $name, array $lines = [] )
	{
		if( is_string($name) && strlen($name) )
		{
			$this->name = $name;
		}
		$this->lines = $lines;
	}

	public function isTextNode(): bool
	{
		return is_null($this->name);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->isTextNode() ? "" : $this->name;
	}

	/**
	 * @return string
	 */
	public function getText(): string
	{
		return ' * ' . ($this->name === null ? '' : ('@' . $this->name . ' ')) . implode("\n * ", $this->lines );
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function getContext( string $glue = " " ): string
	{
		return implode($glue, $this->lines );
	}

	/**
	 * @return array <string>
	 */
	public function getLines(): array
	{
		return $this->lines;
	}

	public function __toString()
	{
		$result = $this->getContext();
		if( ! $this->isTextNode() )
		{
			$result = "@" . $this->getName() . " " . $result;
		}

		return $result;
	}
}