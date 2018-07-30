<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.05.2018
 * Time: 0:29
 */

namespace EApp;

class InvokeCounter
{
	protected $invoke = 0;

	protected $freeze = false;

	protected $frozen = false;

	public function __construct( $freeze )
	{
		$this->freeze = (bool) $freeze;
	}

	public function getCount()
	{
		return $this->invoke;
	}

	public function unfreeze()
	{
		if($this->frozen)
		{
			$this->frozen = false;
		}
		return $this;
	}

	public function cleanCount()
	{
		$this->invoke = 0;
		return $this;
	}

	public function getGenerator()
	{
		for( $i = 0; $i < $this->invoke; $i++ )
		{
			yield $i;
		}
	}

	public function getClosure()
	{
		return function () {
			$this();
		};
	}

	public function __invoke()
	{
		if(! $this->frozen)
		{
			$this->invoke ++;
			if($this->freeze)
			{
				$this->frozen = true;
			}
		}
	}
}