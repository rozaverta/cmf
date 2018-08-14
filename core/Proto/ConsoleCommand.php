<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace EApp\Proto;

use EApp\Host;
use EApp\Prop;
use EApp\System\ConsoleCommands\IO\InputOutputInterface;
use EApp\System\ConsoleCommands\IO\SymfonyInputOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

abstract class ConsoleCommand extends Command
{
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

	private $host = false;

	/**
	 * @var InputOutputInterface
	 */
	private $IO;

	public function __construct( array $docs = [] )
	{
		$this->docs = new Prop($docs);
		parent::__construct( $docs["name"] );

		if( defined("APP_HOST") )
		{
			$this->host = APP_HOST;
		}

		$this->init();
	}

	/**
	 * @return bool
	 */
	public function isHost()
	{
		return defined("APP_HOST");
	}

	/**
	 * @return bool | string
	 */
	public function getHost()
	{
		if( $this->isHost() )
		{
			return $this->host;
		}

		// provide host
		$host = $this->ask('Provide a hostname: ');
		$host = trim($host);
		if( !strlen($host) )
		{
			return $this->getHost();
		}

		// exit ?
		if( $host === 'exit' && $this->confirm("Exit (y/n)? ") )
		{
			exit;
		}

		// reload
		$hosts = Host::getInstance();
		try {
			$reload = $hosts->reload($host);
		}
		catch( \InvalidArgumentException $e ) {
			$this->output->writeln("<error>Warning:</error> " . $e->getMessage());
			return $this->getHost();
		}

		// select host nof found, find else
		if( !$reload )
		{
			$this->output->writeln("<error>Warning:</error> The '{$host}' host not found");
			return $this->getHost();
		}

		// redirect host ?
		if($hosts->getStatus() === "redirect")
		{
			$this->output->writeln("<error>Warning:</error> Host {$host} is already used for redirection, select another");
			return $this->getHost();
		}

		// select host, define constants
		$hosts->define();
		$this->host = $hosts->getHostName();
		return $this->host;
	}

	/**
	 * @return InputOutputInterface
	 */
	protected function getIO()
	{
		return $this->IO;
	}

	protected function hasInstall()
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$system = Prop::file("system");
		return (bool) $system["install"] ?? false;
	}

	protected function hasInstallUpdateProgress()
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$system = Prop::cache("system");
		return
			$system->getOr("update", false) ||
			$system->equiv("status", "update-progress") ||
			$system->equiv("status", "install-progress");
	}

	// input and output

	protected function write( string $string )
	{
		$this->output->writeln($string);
	}

	protected function ask( string $question, $default = '' )
	{
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		return $helper->ask($this->input, $this->getErrorOutput(), new Question($question, $default));
	}

	protected function confirm( string $question, $default = true )
	{
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		return $helper->ask($this->input, $this->getErrorOutput(), new ConfirmationQuestion($question, $default));
	}

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
		$this->IO = new SymfonyInputOutput($this, $input, $output);
		$this->input = $input;
		$this->output = $output;
		$this->exec();
	}

	protected function init() {}

	abstract protected function exec();

	/**
	 * @return OutputInterface
	 */
	private function getErrorOutput()
	{
		if ($this->output instanceof ConsoleOutputInterface) {
			return $this->output->getErrorOutput();
		}
		return $this->output;
	}
}