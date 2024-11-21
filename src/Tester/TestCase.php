<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Tester;

use Nette\Security\SimpleIdentity;
use Stepapo\RequestTester\Config\RequestConfig;
use Stepapo\RequestTester\Config\TestConfig;
use Stepapo\RequestTester\RequestTester;
use Tester\AssertException;
use Tester\Dumper;


abstract class TestCase extends \Tester\TestCase
{
	public function __construct(
		private array $config,
		private RequestTester $requestTester,
		private ?\Closure $identityCallback = null,
		private ?\Closure $refreshCallback = null
	) {}


	public function testRequests()
	{
		$test = TestConfig::createFromArray($this->config);
		foreach ($test->requests as $config) {
			if ($config->reset === true) {
				$this->setUp();
			}
			$this->setUpRequest();
			try {
				$this->request($config);
			} catch (\Exception $e) {
				if ($e instanceof AssertException) {
					throw $e->setMessage(sprintf(
						'%s: %s',
						Dumper::color('red', $config->name),
						$e->origMessage,
					));
				}
				if (str_contains(strtolower($e->getMessage()), 'deadlock')) {
					throw new AssertException(
						sprintf(
							'%s: %s',
							Dumper::color('red', $config->name),
							Dumper::color('white', 'deadlock detected, run again')
						),
						null,
						null,
					);
				}
				throw new AssertException(
					sprintf('%s: ', Dumper::color('red', $config->name)),
					null,
					null,
					$e,
				);
			}
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

		$url = rtrim($config->path, '/') . ($config->query ? ('?' . http_build_query($config->query)) : '');
		$request = $this->requestTester->createRequestFromUrl($url);
		// Method
		$request->setMethod($config->method);
		// Headers
		if ($config->headers) {
			$request->setHeaders($config->headers);
		}
		// RawBody
		if ($config->rawBody) {
			$request->setRawBody($config->rawBody);
		}
		// Identity
		$identity = null;
		if ($config->identity) {
			$identity = $this->identityCallback
				? ($this->identityCallback)($config)
				: new SimpleIdentity($config->identity->id, (array)$config->identity->roles);
			$request->setIdentity($identity);
		}
		// Form
		if ($config->form && $config->form->name != 'none') {
			if ($config->form->name) {
				$send = true;
				if (isset($config->form->post['send']) && $config->form->post['send'] === 'false') {
					$send = false;
					unset($config->form->post['send']);
				}
				$request->setForm(
					$config->form->name,
					$this->requestTester->prepareValues((array)$config->form->post + ($send ? ['send' => '1'] : []), true),
				);
			}
		}
		// Post
		if ($config->post) {
			$request->setPost($this->requestTester->prepareValues((array)$config->post, true));
		}
		$result = $this->requestTester->execute($request, $config->name);
		// Asserts
		if ($config->asserts) {
			if ($config->asserts->httpCode && $config->asserts->httpCode >= 400) {
				$result->assertBadRequest($config->asserts->httpCode);
				return;
			}
			$result = $this->requestTester->getFinalResult($result, $identity);
			if ($config->asserts->renders) {
				foreach ($config->asserts->renders as $renders) {
					$result->assertRenders((array)$renders);
				}
			}
			if ($config->asserts->notRenders) {
				foreach ($config->asserts->notRenders as $notRenders) {
					$result->assertNotRenders((array)$notRenders);
				}
			}
			if ($config->asserts->json !== null) {
				$result->assertJson($this->requestTester->prepareValues($config->asserts->json));
			}
		}
	}


	protected function setUpRequest()
	{
	}


	protected function tearDownRequest()
	{
	}
}
