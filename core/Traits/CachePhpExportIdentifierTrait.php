<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 14:10
 */

namespace EApp\Traits;

use EApp\Support\PhpExportSerialize;

trait CachePhpExportIdentifierTrait
{
	abstract function getId(): int;

	public function phpExportSerialize(): PhpExportSerialize
	{
		return new PhpExportSerialize(
			$this, 'cache', [$this->getId()]
		);
	}
}