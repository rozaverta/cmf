<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 16:32
 */

namespace EApp\Cmd\IO;

use Composer\IO\IOInterface;

class ComposerInputOutput extends AbstractInputOutput
{
	/**
	 * @var IOInterface
	 */
	protected $IO;

	public function __construct( IOInterface $IO )
	{
		$this->IO = $IO;
	}

	protected function outputWrite( string $text )
	{
		$this->IO->write($text, true);
	}

	public function ask( string $question, string $default = "" )
	{
		return $this->IO->ask($question, $default);
	}

	public function confirm( string $question, bool $default = true )
	{
		return $this->IO->askConfirmation($question, $default);
	}
}