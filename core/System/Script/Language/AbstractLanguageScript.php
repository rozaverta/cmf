<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.08.2018
 * Time: 20:06
 */

namespace EApp\System\Script\Language;

use Composer\IO\IOInterface;
use EApp\System\Script\AbstractAddonsScript;

abstract class AbstractLanguageScript extends AbstractAddonsScript
{
	/**
	 * @var string
	 */
	protected $language;

	public function __construct(IOInterface $IO, string $name, string $language)
	{
		parent::__construct($IO, $name);
		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function getLanguage(): string
	{
		return $this->language;
	}
}