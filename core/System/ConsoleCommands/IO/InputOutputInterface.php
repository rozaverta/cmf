<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 15:27
 */

namespace EApp\System\ConsoleCommands\IO;

interface InputOutputInterface
{
	public function write( string $text, ... $args );

	public function table( array $rows, array $header = [], array $properties = [] );

	public function ask( string $question, string $default = "" );

	public function askOptions( array $options, string $title = "" );

	public function askConfig( array $options, string $title = "", array $load = [] );

	public function confirm( string $question, bool $default = true );
}