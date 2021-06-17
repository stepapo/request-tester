<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Tester;

use Nette\Security\SimpleIdentity;
use Stepapo\UrlTester\Config\RequestConfig;
use Stepapo\UrlTester\Helper;
use Stepapo\UrlTester\Config\TestConfig;
use Stepapo\UrlTester\UrlTester;
use Tester\AssertException;


abstract class TestCase extends \Tester\TestCase
{
	public function __construct(
		private array $config,
		private UrlTester $urlTester,
		private Helper $helper,
		private $identityCallback = null,
		private $refreshCallback = null
	) {}


	public function testRequests()
	{
		$test = TestConfig::createFromArray($this->config);
		foreach ($test->requests as $config) {
			if ($config->reset === true) {
				$this->setUp();
			}
			$this->setUpRequest();
			$this->request($config);
			$this->tearDownRequest();
			if ($config->reset === true) {
				$this->tearDown();
			}
		}
	}


	protected function request(RequestConfig $config)
	{
		if ($config->refresh === true && $this->refreshCallback) {
			($this->refreshCallback)();
		}

		$url = '/' . trim($config->url, '/');
		$request = $this->helper->createRequestFromUrl($url);

		//Identity
		$identity = null;
		if ($config->identityConfig) {
			$identity = $this->identityCallback
				? ($this->identityCallback)($config)
				: new SimpleIdentity($config->identityConfig->id, (array)$config->identityConfig->roles);
			$request = $request->withIdentity($identity);
		}

		// Form
		if ($config->formConfig && $config->formConfig->name != 'none') {
			if ($config->formConfig->name) {
				$send = true;
				if (isset($config->formConfig->post['send']) && $config->formConfig->post['send'] === 'false') {
					$send = false;
					unset($config->formConfig->post['send']);
				}
				$request = $request->withForm(
					$config->formConfig->name,
					$this->helper->prepareValues((array)$config->formConfig->post + ($send ? ['send' => '1'] : []), true)
				);
			}
		}

		// Post
		if ($config->post) {
			$request = $request->withPost($this->helper->prepareValues((array)$config->post, true));
		}

		$result = $this->urlTester->execute($request, $config->name);

		// Asserts
		if ($config->assertConfig) {
			if ($config->assertConfig?->httpCode && $config->assertConfig->httpCode >= 400) {
				$result->assertBadRequest($config->assertConfig->httpCode);
				return;
			}
			$result = $this->helper->getFinalResult($result, $identity);
			if ($config->assertConfig->renders) {
				foreach ($config->assertConfig->renders as $renders) {
					$result->assertRenders((array)$renders);
				}
			}
			if ($config->assertConfig->notRenders) {
				foreach ($config->assertConfig->notRenders as $notRenders) {
					$result->assertNotRenders((array)$notRenders);
				}
			}
			if ($config->assertConfig->json) {
				$result->assertJson($this->helper->prepareValues($config->assertConfig->json));
			}
		}
	}
	public function runTest(string $method, array $args = null): void
	{
		try {
			parent::runTest($method, $args);
		} catch (AssertException $e) {
			throw $e->setMessage(sprintf('%s', $e->origMessage));
		}
	}


	protected function setUpRequest()
	{
	}


	protected function tearDownRequest()
	{
	}
}
