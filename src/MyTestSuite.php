<?php

require_once ('MYOWebTestCase.php');
require_once ('MySimpleBrowser.php');


class MyTestSuite extends TestSuite {

	protected $timer;

	protected $previousNumberOfAllFetches;
	protected $previousNumberOfWebRequests;

	public function __construct()
	{
		parent::__construct();

		$this->timer = microtime(true);

		$this->previousNumberOfAllFetches = GlobalCounters::$numberOfAllFetches;
		$this->previousNumberOfWebRequests = GlobalCounters::$numberOfWebRequests;

		error_reporting(-1);
		ini_set('display_errors', true);
	}


	public function __destruct()
	{
		$elapsedTime = round(microtime(true) - $this->timer, 1);
		$fileName = get_class($this) . ".php";
		
		$numberOfAllFetches = GlobalCounters::$numberOfAllFetches - $this->previousNumberOfAllFetches;
		$numberOfWebRequests = GlobalCounters::$numberOfWebRequests - $this->previousNumberOfWebRequests;

		echo "<br />Total Elapsed time: $elapsedTime seconds (all fetches: $numberOfAllFetches, web requests: $numberOfWebRequests).<br />";
	}

}

