<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.09.2017
 * Time: 3:23
 */

namespace EApp;

class Benchmark
{
	protected $start;

	protected $end;

	protected $points = [];

	public function __construct()
	{
		$this->start = microtime(true);
		$this->end = $this->start;
	}

	public function setPoint( $name = null )
	{
		$end = $this->end;
		$this->end = microtime(true);
		$this->points[] = [$this->end, $this->end - $end, $name];
		return $this;
	}

	public function getPoints()
	{
		return $this->points;
	}

	public function start()
	{
		return $this->start;
	}

	public function end()
	{
		return $this->end;
	}

	public function delta()
	{
		return $this->end - $this->start;
	}
}