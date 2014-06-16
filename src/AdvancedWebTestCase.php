<?php

require_once(dirname(__FILE__) . '/../simpletest/web_tester.php');

require_once ('MySimpleBrowser.php');
require_once('GlobalCounters.php');


class AdvancedWebTestCase extends WebTestCase
{
	public $createDate = '2013-12-05';
	public $updateDate = '2014-04-09';

	public $fullTitle;
	public $shortTitle;
	
	protected $host;
	protected $timer;

	protected $previousNumberOfAllFetches;
	protected $previousNumberOfWebRequests;

	protected $urlAlreadyCheckedList;


	public function __construct()
	{
		parent::__construct();
		
		$this->timer = microtime(true);

		$this->previousNumberOfAllFetches = GlobalCounters::$numberOfAllFetches;
		$this->previousNumberOfWebRequests = GlobalCounters::$numberOfWebRequests;

		$this->host = $_SERVER['HTTP_HOST'];

		$this->urlAlreadyCheckedList = array();
	}


	public function __destruct()
	{
		$elapsedTime = round(microtime(true) - $this->timer, 1);
		$fileName = get_class($this) . ".php";
		$title = "";
		if ( isset($this->fullTitle) )
		{
			$title = "($this->fullTitle) ";
		}

		$numberOfAllFetches = GlobalCounters::$numberOfAllFetches - $this->previousNumberOfAllFetches;
		$numberOfWebRequests = GlobalCounters::$numberOfWebRequests - $this->previousNumberOfWebRequests;

		echo "$fileName - $this->updateDate - $title- Elapsed time: $elapsedTime seconds (all fetches: $numberOfAllFetches, web requests: $numberOfWebRequests).<br />";
	}


	public function createBrowser()
	{
		return new MySimpleBrowser();
	}


	public function assertNoFieldByName($name, $expected = true, $message = null)
	{
		if ( empty($message) )
		{
			$message = "input field with name [$name] exists";
		}
		$content = $this->getBrowser()->getContent();

		$result = preg_match("/<input[^>]+name=['\"]$name/", $content, $matches);

		$this->assertEqual($result, 0, $message);
	}


	protected function makeAbsolute($link, $pageUrl)
	{
		$linkSimpleUrl = new SimpleUrl($link);
		$linkUrl = $linkSimpleUrl->makeAbsolute($pageUrl)->asString();

		return $linkUrl;
	}


	protected function getCSSFileLinkList($content)
	{
		$cssFileLinkListArray = array();
		preg_match_all('/<link.*?href=["\'](.*?\.css.*?)["\']/', $content, $cssFileLinkListArray);

		return $cssFileLinkListArray[1];
	}


	protected function getImageLinksFromCSSFile($cssFileLink, $pageUrl)
	{
		$cssFileUrl = $this->makeAbsolute($cssFileLink, $pageUrl);

		$simpleBrowser = new MySimpleBrowser();
		$content = $simpleBrowser->get($cssFileUrl);
		$this->assertTrue($content !== false);
		$this->assertEqual($simpleBrowser->getResponseCode(), 200);

		$urlArray = array();
		preg_match_all('/url\s*\(["\']?(.*?)["\']?\)/', $content, $urlArray);

		return $urlArray[1];
	}


	protected function checkForBrokenLinksInCSSFiles($content, $pageUrl = null)
	{
		if ( empty($pageUrl) )
		{
			$pageUrl = $this->getUrl();
		}
		$simpleBrowser = new MySimpleBrowser();

		$cssFileList = $this->getCSSFileLinkList($content);
		$imageLinkList = array();
		foreach($cssFileList as $cssFileLink)
		{
			$imageLinkList = $this->getImageLinksFromCSSFile($cssFileLink, $pageUrl);

			foreach($imageLinkList as $imageLink)
			{
				$cssFileSimpleUrl = new SimpleUrl($cssFileLink);
				if ($cssFileSimpleUrl->getHost() === false)
				{
					// $cssFileLink is relative link
					$imageUrl = $this->makeAbsolute($imageLink, $pageUrl);
				} else
				{
					$imageUrl = $this->makeAbsolute($imageLink, $cssFileLink);
				}


				if ( !in_array($imageUrl, $this->urlAlreadyCheckedList) )
				{
					$simpleBrowser->get($imageUrl);
					$this->assertEqual($simpleBrowser->getResponseCode(), 200, "original url from CSS file: $imageLink. 
						Converted into absolute url: $imageUrl. Found in CSS file:  $cssFileLink. Page: $pageUrl. %s");
					$this->urlAlreadyCheckedList[] = $imageUrl;
				}
			}

		}
	}


	protected function getImageLinkList($content, $pageUrl)
	{
		$imgArray = array();
		preg_match_all('/<img.*?src=["\'](.*?)["\']/', $content, $imgArray);

		return $imgArray[1];
	}


	protected function checkForBrokenImageLinks($content, $pageUrl = null)
	{
		if ( empty($pageUrl) )
		{
			$pageUrl = $this->getUrl();
		}
		
		$imageLinkList = $this->getImageLinkList($content, $pageUrl);

		$simpleBrowser = new MySimpleBrowser();
		foreach($imageLinkList as $imageLink)
		{
			$imageUrl = $this->makeAbsolute($imageLink, $pageUrl);

			if ( !in_array($imageUrl, $this->urlAlreadyCheckedList) )
			{
				$simpleBrowser->get($imageUrl);
				$this->assertEqual($simpleBrowser->getResponseCode(), 200, "url=$imageLink. Found on the page $pageUrl. %s");
				$this->urlAlreadyCheckedList[] = $imageUrl;
			}
		}
	}


	protected function getALinkList($content)
	{
		$hrefArray = array();
		preg_match_all('/<a.*?href=["\'](.*?)["\']/', $content, $hrefArray);

		return $hrefArray[1];
	}


	protected function checkForBrokenALinks($content, $pageUrl, $comment = null)
	{
		$hrefList = $this->getALinkList($content);

		$simpleBrowser = new MySimpleBrowser();
		foreach($hrefList as $url)
		{
			if (substr($url, 0, 7) == "mailto:")
			{
				continue;
			}

			if (substr($url, 0, 11) == "javascript:")
			{
				continue;
			}

			if (substr($url, 0, 4) != "http")
			{
				$url = $this->makeAbsolute($url, $pageUrl);
			}

			if ( !in_array($url, $this->urlAlreadyCheckedList) )
			{
				$simpleBrowser->get($url);

				$encodedUrl = str_replace('%', '&#37;', $url); // Facebook url has '%' in it that screws up line below
				$this->assertEqual($simpleBrowser->getResponseCode(), 200, "Testing for broken link. link=$encodedUrl (page url=$pageUrl, comment=$comment). %s");
				$this->urlAlreadyCheckedList[] = $url;
			}
		}
	}


	protected function checkValidation($content, $url = null, $comment = null)
	{
		$validatorUrl = 'http://validator.w3.org/check';
		$params = array( 'fragment'=>$content, 'output'=>'json');

		$simpleBrowser = new MySimpleBrowser();

		$jsonStr = $simpleBrowser->post($validatorUrl, $params);
		$this->assertEqual($simpleBrowser->getResponseCode(), 200, "HTML validator page is not available (attempt to validate url=$url). %s");

		$object = json_decode($jsonStr);
		//$this->assertNotEqual($object, null, "HTML validator returned non-valid JSON for url=$url. Probably some error at http://validator.w3.org/check ");
		if ($object == null)
		{
			return;
		}

		$numberOfErrors = 0;
		$numberOfWarnings = 0;
		foreach($object->messages as $message)
		{
			if ($message->type == 'error')
			{
				$numberOfErrors++;
			} else
			{
				$numberOfWarnings++;
			}
		}

		$this->assertEqual( count($object->messages), 0, count($object->messages) . " issues. ($numberOfErrors errors, $numberOfWarnings warnings) of HTML validation");

		if ( count($object->messages) != 0)
		{
			if ( !empty($url) )
			{
				echo "url=$url<br />";
			}

			if ( !empty($comment) )
			{
				echo "comment=$comment<br />";
			}
		}
	}


	protected function checkPHPNotices($content, $url = null, $comment = null)
	{
		$matches = array();
		$result = preg_match('/PHP Notice/', $content, $matches);
		$this->assertEqual($result, 0, "PHP Notice found for url=$url, comment=$comment. %s");

		$result += preg_match('/(?<!=")\bspirin[^@]/i', $content, $matches); // check for debug messages that might have been left.
		$this->assertEqual($result, 0, "Debug messages found for url=$url, comment=$comment. %s");

		if ($comment == "non-logged user")
		{
			$resultFacebook = preg_match('/"og:/', $content, $matches); // check for Facebook tags
			//$this->assertEqual($resultFacebook, 1, "url=$link, comment=$comment. %s");
		}

		if ($result != 0)
		{
			echo $content;
		}
	}


	protected function generalCheckForPage($content, $pageUrl, $comment = null)
	{
		$this->checkForBrokenImageLinks($content, $pageUrl);
		$this->checkForBrokenALinks($content, $pageUrl, $comment);
		$this->checkForBrokenLinksInCSSFiles($content, $pageUrl);
		$this->checkValidation($content, $pageUrl);
		$this->checkPHPNotices($content, $pageUrl, $comment);
	}


} // class AdvancedWebTestCase
