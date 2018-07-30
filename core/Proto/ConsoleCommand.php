<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\Proto;

use Symfony\Component\Console\Command\Command;

abstract class ConsoleCommand extends Command
{
	protected $docs = [];

	public function __construct( array $docs = [] )
	{
		$this->docs = $docs;
		parent::__construct( $this->docs["name"] );
	}

	protected function configure()
	{
		// the short description shown while running "php bin/console list"
		if( isset($this->docs["description"]) )
		{
			$this->setDescription($this->docs["description"]);
		}

		// the full command description shown when running the command with
		// the "--help" option
		if( isset($this->docs["help"]) )
		{
			$this->setHelp( 'Hello world help text :)' );
		}
	}
}
