<?php

namespace Codeburner\Routing;

interface RouteFilterInterface
{

	/**
	 * Execute a filter of a request.
	 *
	 * @return boolean
	 */
	public function handle();

}
