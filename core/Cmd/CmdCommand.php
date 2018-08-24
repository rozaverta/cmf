<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\Cmd;

use EApp\Cmd\Api\SystemHostTrait;
use EApp\Prop;
use EApp\Cmd\IO\SymfonyInputOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CmdCommand extends Command
{
	use SystemHostTrait;

	/**
	 * @var Prop
	 */
	protected $docs;

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	public function __construct( array $docs = [] )
	{
		$this->docs = new Prop($docs);
		parent::__construct( $docs["name"] );
		$this->init();
	}

	// input and output

	protected function configure()
	{
		// the short description shown while running "php bin/console list"
		if( $this->docs->getIs("description") )
		{
			$this->setDescription($this->docs["description"]);
		}

		// the full command description shown when running the command with
		// the "--help" option
		if( $this->docs->getIs("help") )
		{
			$this->setHelp($this->docs["help"]);
		}
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$this->setIO(new SymfonyInputOutput($this, $input, $output));
		$this->input = $input;
		$this->output = $output;
		$this->exec();
	}

	protected function init() {}

	abstract protected function exec();
}