<?php

require_once('MySimpleUserAgent.php');


class MySimpleBrowser extends SimpleBrowser
{
	protected $userAgentHeader = 'User-Agent: Firefox/2.0.0.1';
	protected $mySimpleUserAgent; // we need this field because we can not access private $user_agent in SimpleBrowser


	protected function createUserAgent()
	{
		$this->mySimpleUserAgent = new MySimpleUserAgent();
		$this->mySimpleUserAgent->addHeader($this->userAgentHeader);

		return $this->mySimpleUserAgent;
	}


    protected function fetch($url, $encoding, $depth = 0) 
    {
        $response = $this->mySimpleUserAgent->fetchResponse($url, $encoding);
        if ($response->isError() ||
            strpos($response->getHeaders()->getMimeType(), "image") !== false) {

            $result = new SimplePage($response);
        } else
        {
            $result = $this->parse($response, $depth);
        }

        return $result;
    }


} // class MySimpleBrowser
