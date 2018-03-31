<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 21:36
 */

namespace EApp\Support\Interfaces;

use EApp\App;

interface SingletonCompletable
{
	public function instanceComplete( App $app );
}