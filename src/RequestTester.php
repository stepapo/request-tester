<?php

declare(strict_types=1);

namespace Stepapo\RequestTester;

use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as AppRequest;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;
use Stepapo\RequestTester\Mock\HttpRequest;
use Tester\Assert;
use Tester\Expect;

//use Nette\Http\Request as HttpRequest;


class RequestTester
{
	public function __construct(
		private Session $session,
		private IPresenterFactory $presenterFactory,
		private Router $router,
		private Application $application,
		private IRequest $httpRequest,
		private User $user,
	) {}


	public function execute(TestRequest $testRequest, string $name): TestResult
	{
		$applicationRequest = $this->createApplicationRequest($testRequest);
		Arrays::invoke($this->application->onRequest, $this->application, $applicationRequest);
		$presenter = $this->createPresenter($testRequest);
		try {
			$response = $presenter->run($applicationRequest);
			$badRequestException = $applicationRequest->getParameter('exception');
		} catch (BadRequestException $badRequestException) {
			$response = null;
		}
		if ($applicationRequest->getParameter(Presenter::SignalKey) && method_exists($presenter, 'isSignalProcessed')) {
			if (!$presenter->isSignalProcessed()) {
				if ($badRequestException) {
					$cause = 'BadRequestException with code ' . $badRequestException->getCode() . ' and message "' . $badRequestException->getMessage() . '"';
				} else {
					assert($response !== null);
					$cause = get_class($response);
				}
				Assert::fail('Signal has not been processed at all, received ' . $cause);
			}
		}

		$result = new TestResult($response, $badRequestException, $name);

		return $result;
	}


	public function createRequest(string $url, string $presenterName): TestRequest
	{
		return new TestRequest($url, $presenterName, $this->session);
	}


	protected function createPresenter(TestRequest $request): IPresenter
	{
		$this->loginUser($request);
		$this->setupHttpRequest($request);
		$presenter = $this->presenterFactory->createPresenter($request->presenterName);
		if ($presenter instanceof Presenter) {
			$this->setupUIPresenter($presenter);
		}

		return $presenter;
	}


	protected function createApplicationRequest(TestRequest $testRequest): AppRequest
	{
		return new AppRequest(
			$testRequest->presenterName,
			$testRequest->post ? 'POST' : $testRequest->methodName,
			$testRequest->parameters,
			$testRequest->post,
		);
	}


	protected function loginUser(TestRequest $request): void
	{
		$this->user->logout(true);
		$identity = $request->identity;
		if ($identity) {
			$this->user->login($identity);
		}
	}


	protected function setupHttpRequest(TestRequest $request): void
	{
//		$this->httpRequest = new HttpRequest(
//			url: new UrlScript($request->url),
//			post: $request->post,
//			headers: $request->headers,
//			method: ($request->post) ? 'POST' : $request->methodName,
//			rawBodyCallback: fn() => $request->rawBody
//		);
		$this->httpRequest->method = ($request->post) ? 'POST' : $request->methodName;
		$this->httpRequest->headers = $request->headers + $this->httpRequest->headers;
		$this->httpRequest->post = $request->post;
		$this->httpRequest->url = new UrlScript($request->url);
		$this->httpRequest->rawBodyCallback = fn() => $request->rawBody;
//		\Closure::bind(function () use ($request) {
//			/** @var Request $this */
//			$this->method = ($request->post) ? 'POST' : $request->methodName;
//			$this->headers = $request->headers + $this->headers;
//			$this->post = $request->post;
//			$this->url = new UrlScript($request->url);
//			$this->rawBodyCallback = fn() => $request->rawBody;
//		}, $this->httpRequest, Request::class)->__invoke();
	}


	protected function setupUIPresenter(Presenter $presenter): void
	{
		$presenter->autoCanonicalize = false;
		$presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;
	}


	public function getFinalResult(TestResult $result, ?IIdentity $identity): TestResult
	{
		$response = $result->getResponse();
		if ($response instanceof RedirectResponse) {
			$request = $this->createRequestFromRedirectResponse($response);
			if ($identity) {
				$request = $request->setIdentity($identity);
			}
			$result = $this->execute($request, $result->name);
			return $this->getFinalResult($result, $identity);
		}

		return $result;
	}


	private function createRequestFromRedirectResponse(RedirectResponse $response): TestRequest
	{
		return $this->createRequestFromUrl($response->getUrl());
	}


	public function createRequestFromUrl(string $url): TestRequest
	{
		$httpRequest = new HttpRequest(new UrlScript(ltrim($url, ':')));

		$params = $this->router->match($httpRequest);

		$presenter = $params[Presenter::PresenterKey] ?? 'Error';
		$params = isset($params[Presenter::PresenterKey]) ? $params : ['exception' => new BadRequestException()];
		unset($params[Presenter::PresenterKey]);

		$request = $this->createRequest($url, $presenter)
			->setParameters($params);

		return $request;
	}


	public function prepareValues(array $values, bool $toString = false): array
	{
		$return = [];
		foreach ($values as $key => $value) {
			$return[$key] = is_array($value)
				? $this->prepareValues($value, $toString)
				: $this->prepareValue($value, $toString);
		}
		return $return;
	}


	private function prepareValue($wildcard, bool $toString = false)
	{
		switch ($wildcard) {
			case 'DATE_YESTERDAY':
				return (new DateTime('yesterday'))->format('Y-m-d');
			case 'DATE_TODAY':
				return (new DateTime())->format('Y-m-d');
			case 'DATE_TOMORROW':
				return (new DateTime('tomorrow'))->format('Y-m-d');
			case 'DOW_YESTERDAY':
				return (new DateTime('yesterday'))->format('N');
			case 'DOW_TODAY':
				return (new DateTime())->format('N');
			case 'DOW_TOMORROW':
				return (new DateTime('tomorrow'))->format('N');
			case 'WEEK_MONDAY_LAST':
				return (new DateTime('monday last week'))->format('Y-m-d');
			case 'WEEK_MONDAY_THIS':
				return (new DateTime('monday this week'))->format('Y-m-d');
			case 'WEEK_MONDAY_NEXT':
				return (new DateTime('monday next week'))->format('Y-m-d');
			case 'EXPECT_INT':
				return Expect::type('int');
			default:
				return is_numeric($wildcard) && $toString ? (string) $wildcard : $wildcard;
		}
	}
}
