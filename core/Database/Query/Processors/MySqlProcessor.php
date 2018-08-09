<?php

namespace EApp\Database\Query\Processors;

class MySqlProcessor extends Processor
{
	/**
	 * Process the results of a column listing query.
	 *
	 * @param  array  $results
	 * @return array
	 */
	public function processColumnListing($results)
	{
		return array_map(function ($result) {
			return is_object($result) ? $result->column_name : $result['column_name'];
		}, $results);
	}
}