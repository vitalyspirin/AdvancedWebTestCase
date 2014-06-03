<?php

require_once('MySimpleUserAgent.php');


class MySimpleBrowser extends SimpleBrowser
{
	protected $userAgentHeader = 'User-Agent: Firefox/2.0.0.1';


	protected function createUserAgent()
	{
		$mySimpleUserAgent = new MySimpleUserAgent();
		$mySimpleUserAgent->addHeader($this->userAgentHeader);

		return $mySimpleUserAgent;
	}


} // class MySimpleBrowser
