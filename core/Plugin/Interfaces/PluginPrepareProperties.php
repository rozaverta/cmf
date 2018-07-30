<?php

namespace EApp\Plugin\Interfaces;

interface PluginPrepareProperties
{
	/**
	 * Prepare plugin properties and get cache data
	 *
	 * @param array $data
	 * @return array
	 */
	public function prepareProperties( array $data );
}