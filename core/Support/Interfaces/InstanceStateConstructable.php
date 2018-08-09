<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 23:07
 */

namespace EApp\Support\Interfaces;


interface InstanceStateConstructable extends CreateInstanceInterface
{
	public function getInstanceState(): array;
}