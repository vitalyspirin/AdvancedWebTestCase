<?php

class MySimpleUserAgent extends SimpleUserAgent
{

	protected function fetch($url, $encoding)
	{
		GlobalCounters::$numberOfAllFetches++;

		return parent::fetch($url, $encoding);
	}


	protected function fetchWhileRedirected($url, $encoding)
	{
		GlobalCounters::$numberOfWebRequests++;

		return parent::fetchWhileRedirected($url, $encoding);
	}


}
