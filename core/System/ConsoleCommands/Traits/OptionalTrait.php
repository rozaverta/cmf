<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.07.2018
 * Time: 20:10
 */

namespace EApp\System\ConsoleCommands\Traits;

/**
 * Trait OptionalTrait
 *
 * @property \Symfony\Component\Console\Input\Input $input
 *
 * @package EApp\System\ConsoleCommands\Traits
 */
trait OptionalTrait
{
	protected $option_reserved = ["help"];

	protected function optional( string $name, array $required = [], array $not = [] )
	{
		if( !$this->input->hasOption($name) )
		{
			return false;
		}

		$this->optionalRequired($required);
		$this->optionalNot($not);

		return $this->input->getOption($name);
	}

	protected function optionalRequired( array $only, bool $throw = true )
	{
		if( count($only) < 1 )
		{
			return true;
		}

		foreach($only as $name)
		{
			if(! $this->input->hasOption($name))
			{
				if( $throw )
				{
					throw new \InvalidArgumentException("You must use the --{$name} option for this combination of the query");
				}
				else
				{
					return false;
				}
			}
		}

		return true;
	}

	protected function optionalNot( array $not, bool $throw = true )
	{
		$count = count($not);
		if( $count < 1 )
		{
			return true;
		}

		$not_all = $count === 1 && $not[0] === "*";
		foreach(array_keys($this->input->getOptions()) as $name)
		{
			// system option, ignore
			if( in_array($name, $this->option_reserved, true) )
			{
				continue;
			}

			if($not_all || ! $not_all && in_array($name, $not))
			{
				if( $throw )
				{
					throw new \InvalidArgumentException("You can not use the --{$name} option for this combination of the query");
				}
				else
				{
					return false;
				}
			}
		}

		return true;
	}

	protected function optionalChoice( array $options, \Closure $choice_callback )
	{
		if( count($options) < 1 )
		{
			throw new \InvalidArgumentException("No options selected");
		}

		$found = false;
		$value = null;

		foreach(array_keys($this->input->getOptions()) as $name)
		{
			if( in_array($name, $options, true) )
			{
				if($found)
				{
					throw new \InvalidArgumentException("You can not use the --{$name} option and the {--$found} at the same time");
				}
				else
				{
					$found = $name;
					$value = $this->input->getOption($name);
				}
			}
		}

		if(!$found)
		{
			throw new \InvalidArgumentException( "You must use one of the following parameters: --" . implode(", --", $options) );
		}

		return $choice_callback($found, $value);
	}

}