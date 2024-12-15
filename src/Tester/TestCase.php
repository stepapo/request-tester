<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Tester;

use Nette\Security\SimpleIdentity;
use Stepapo\RequestTester\Config\Request;
use Stepapo\RequestTester\Config\Test;
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
		$test = Test::createFromArray($this->config);
		foreach ($test->requests as $request) {
			if ($request->reset === true) {
				$this->setUp();
			}
			$this->setUpRequest();
			try {
				$this->request($request);
			} catch (\Exception $e) {
				if ($e instanceof AssertException) {
					throw $e->setMessage(sprintf(
						'%s: %s',
						Dumper::color('red', $request->name),
						$e->origMessage,
					));
				}
				if (str_contains(strtolower($e->getMessage()), 'deadlock')) {
					throw new AssertException(
						sprintf(
							'%s: %s',
							Dumper::color('red', $request->name),
							Dumper::color('white', 'deadlock detected, run again')
						),
						null,
						null,
					);
				}
				throw new AssertException(
					sprintf('%s: ', Dumper::color('red', $request->name)),
					null,
					null,
					$e,
				);
			}
			$this->tearDownRequest();
			if ($request->reset === true) {
				$this->tearDown();
			}
		}
	}


	protected function request(Request $request)
	{
		if ($request->refresh === true && $this->refreshCallback) {
			($this->refreshCallback)();
		}

		$url = rtrim($request->path, '/') . ($request->query ? ('?' . http_build_query($request->query)) : '');
		$testRequest = $this->requestTester->createRequestFromUrl($url);
		// Method
		$testRequest->setMethod($request->method);
		// Headers
		if ($request->headers) {
			$testRequest->setHeaders($request->headers);
		}
		// RawBody
		if ($request->rawBody) {
			$testRequest->setRawBody($request->rawBody);
		}
		// Identity
		$identity = null;
		if ($request->identity) {
			$identity = $this->identityCallback
				? ($this->identityCallback)($request)
				: new SimpleIdentity($request->identity->id, (array)$request->identity->roles);
			$testRequest->setIdentity($identity);
		}
		// Form
		if ($request->form && $request->form->name != 'none') {
			if ($request->form->name) {
				$send = true;
				if (isset($request->form->post['send']) && $request->form->post['send'] === 'false') {
					$send = false;
					unset($request->form->post['send']);
				}
				$testRequest->setForm(
					$request->form->name,
					$this->requestTester->prepareValues((array)$request->form->post + ($send ? ['send' => '1'] : []), true),
				);
			}
		}
		// Post
		if ($request->post) {
			$testRequest->setPost($this->requestTester->prepareValues((array)$request->post, true));
		}
		$result = $this->requestTester->execute($testRequest, $request->name);
		// Asserts
		if ($request->asserts) {
			if ($request->asserts->httpCode && $request->asserts->httpCode >= 400) {
				$result->assertBadRequest($request->asserts->httpCode);
				return;
			}
			$result = $this->requestTester->getFinalResult($result, $identity);
			if ($request->asserts->renders) {
				foreach ($request->asserts->renders as $renders) {
					$result->assertRenders((array)$renders);
				}
			}
			if ($request->asserts->notRenders) {
				foreach ($request->asserts->notRenders as $notRenders) {
					$result->assertNotRenders((array)$notRenders);
				}
			}
			if ($request->asserts->json !== null) {
				$result->assertJson($this->requestTester->prepareValues($request->asserts->json));
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
