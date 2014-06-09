AdvancedWebTestCase
===================

AdvancedWebTestCase class extending SimpleTest class WebTestCase and adding additional features

### Quick Start and Examples
```php
class ExampleTest extends AdvancedWebTestCase
{ 

	public $fullTitle = 'Example to demonstrate usage of "AdvancedWebTestCase" class';
	public $shortTitle = 'example';

	public $createDate = '2014-06-06';
	public $updateDate = '2014-06-06';


	public function test1()
	{
		$url = "https://www.google.com";
		$content = $this->get($url);

		$this->checkForBrokenImageLinks($content, $url);
		$this->checkForBrokenALinks($content, $url);
		$this->checkForBrokenLinksInCSSFiles($content, $url);
		$this->checkValidation($content, $url);
		$this->checkPHPNotices($content, $url);

		// all above functions can be called by calling $this->generalCheckForPage($content, $url)
	}

}
```
