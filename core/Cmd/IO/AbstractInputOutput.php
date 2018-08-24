<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 16:05
 */

namespace EApp\Cmd\IO;

use EApp\Support\Str;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractInputOutput implements InputOutputInterface
{
	/**
	 * @var OutputInterface
	 */
	protected $output;

	abstract protected function outputWrite( string $text );

	public function write( string $text, ... $args )
	{
		if( count( $args ) )
		{
			$args = array_map( function ( $argument ) {
				if( is_bool( $argument ) ) {
					return $argument ? '<info>true</info>' : '<error>false</error>';
				} else if( is_int( $argument ) || is_float( $argument ) ) {
					return '<comment>' . $argument . '</comment>';
				} else {
					return '<comment>"' . (string) $argument . '"</comment>';
				}
			}, $args );

			$text = vsprintf( $text, $args );
		}

		$this->outputWrite( $text );
	}

	public function table( array $rows, array $header = [], array $styles = [], \Closure $closure = null )
	{
		if( !isset($this->output) )
		{
			$this->output = new ConsoleOutput();
		}

		$table = new Table( $this->output );

		foreach($styles as $style)
		{
			$table->setStyle($style);
		}

		if(count($header))
		{
			$table->setHeaders($header);
		}

		if($closure)
		{
			$closure($table);
		}

		$table->addRows($rows);

		$table->render();
	}

	public function askOptions( array $options, string $title = "" )
	{
		if( !count( $options ) )
		{
			return false;
		}

		if( strlen($title) > 0 )
		{
			$this->writeHeader($title);
		}

		$get = [];
		$min = 1;
		$max = 0;

		foreach($options as $name => $option)
		{
			if( is_string($option) )
			{
				$option = new Option($option, $name);
				$options[$name] = $option;
			}

			if( $option instanceof Option )
			{
				$get[++$max] = $option->getValue();
				$this->write('<info>' . $max . '</info> ' . $option->getAnswer());
			}
			else
			{
				throw new \InvalidArgumentException("The option item must be inherited of " . Option::class);
			}
		}

		return $get[ $this->getOption($min, $max) ];
	}

	public function askConfig( array $options, string $title = "", array $load = [] )
	{
		if( !count($options) )
		{
			return [];
		}

		if( strlen($title) )
		{
			$this->writeHeader($title);
		}

		return $this->getConfig( $options, $load );
	}

	private function getOption( $min, $max )
	{
		$val = $this->ask("Enter a option number from <comment>{$min}</comment> to <comment>{$max}</comment>: ", "");
		$val = trim($val);
		if( !is_numeric($val) )
		{
			return $this->getOption($min, $max);
		}

		$val = (int) $val;
		if( $val < $min || $val > $max )
		{
			return $this->getOption($min, $max);
		}

		return $val;
	}

	private function getConfig( array $options, array $load = [] ): array
	{
		// enter data

		foreach($options as $name => $option)
		{
			if( is_string($name) && is_string($option) )
			{
				$option = new ConfigOption($name, $option);
				$options[$name] = $option;
			}

			if( $option instanceof ConfigOption )
			{
				$this->askConfigOption($option, $load);
			}
			else
			{
				throw new \InvalidArgumentException("The option item must be inherited of " . ConfigOption::class);
			}
		}

		// check data

		$this->writeHeader("Check the data");

		/** @var ConfigOption $option */
		foreach( $options as $name => $option )
		{
			$name = $option->getName();
			if( isset($load[$name]) )
			{
				$val = $load[$name];

				if( is_bool($val) ) $val = $val ? '<info>yes</info>' : '<error>no</error>';
				else if( is_int($val) || is_float($val) ) $val = '<comment>' . $val . '</comment>';
				else $val = '<info>' . $val . '</info>';

				$this->write($option->getTitle() . ": " . $val);
			}
		}

		// confirm

		if( !$this->confirm("Continue (y/n)? ") )
		{
			$this->write("<info>$</info> -- Confirm");
			return $this->getConfig($options, $load);
		}

		return $load;
	}

	private function askConfigOption(ConfigOption $option, & $load)
	{
		$name    = $option->getName();
		$type    = $option->getType();
		$title   = $option->getAnswer();
		$default = $load[$name] ?? $option->getValue();

		if( $type === "bool" )
		{
			if( !is_bool($default) )
			{
				$default = $default === 'true' || $default === '';
			}

			$result = $this->confirm(str_replace('%s', ($default ? 'true' : 'false'), $title) . " (y/n): ", $default);
		}
		else
		{
			$default = preg_replace_callback('/\{(.*?)\}/', function($m) use (& $load) {
				$val = $load[$m[1]] ?? "";
				return is_bool($val) ? ($val ? 'true' : 'false') : $val;
			}, $default);

			$result = $this->ask(str_replace('%s', $default, $title) . ": ", $default);
			$result = trim($result);
		}

		try {
			$option->setValue($result);
		}
		catch( \InvalidArgumentException $e )
		{
			$this->write("<error>Wrong:</error> " . $e->getMessage());
			$this->askConfigOption($option, $load);
			return;
		}

		$option->fill($load);
	}

	private function writeHeader( string $text )
	{
		$len = Str::len($text);
		$this->write('<comment>+' . str_repeat('-', $len + 8) . '+</comment>');
		$this->write('<comment>|</comment>    ' . $text . '    <comment>|</comment>');
		$this->write('<comment>+' . str_repeat('-', $len + 8) . '+</comment>');
	}
}