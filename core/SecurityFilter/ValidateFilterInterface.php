<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.12.2017
 * Time: 16:07
 */

namespace EApp\SecurityFilter;


interface ValidateFilterInterface
{
	public function __construct( $name = null, array $props = [] );

	public function filter( $value );
}