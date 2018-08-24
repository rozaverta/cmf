<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 16:04
 */

namespace EApp\Cmd\IO;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class SymfonyInputOutput extends AbstractInputOutput
{
	/**
	 * @var InputInterface
	 */
	protected $command;

	/**
	 * @var InputInterface
	 */
	protected $input;

	public function __construct( Command $command, InputInterface $input, OutputInterface $output )
	{
		$this->command = $command;
		$this->input = $input;
		$this->output = $output;
	}

	protected function outputWrite( string $text )
	{
		$this->output->writeln($text);
	}

	public function ask( string $text, string $default = "" )
	{
		/** @var QuestionHelper $helper */
		$helper = $this->command->getHelper('question');
		return $helper->ask($this->input, $this->getErrorOutput(), new Question($text, $default));
	}

	public function confirm( string $text, bool $default = true )
	{
		/** @var QuestionHelper $helper */
		$helper = $this->command->getHelper('question');
		return $helper->ask($this->input, $this->getErrorOutput(), new ConfirmationQuestion($text, $default));
	}

	/**
	 * @return OutputInterface
	 */
	private function getErrorOutput()
	{
		if ($this->output instanceof ConsoleOutputInterface)
		{
			return $this->output->getErrorOutput();
		}
		return $this->output;
	}
}