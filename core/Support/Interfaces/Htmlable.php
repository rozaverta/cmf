<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 17:21
 */

namespace EApp\Support\Interfaces;

interface Htmlable
{
	/**
	 * Get content as a string of HTML.
	 *
	 * @param bool $as_xml
	 * @return string
	 */
	public function toHtml( $as_xml = false );
}