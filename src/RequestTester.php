<?php declare(strict_types=1);

namespace Stepapo\RequestTester;

use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Stepapo\RequestTester\PresenterTester\TestPresenterRequest;
use Stepapo\RequestTester\PresenterTester\TestPresenterResult;
use Tester\Assert;


class RequestTester
{
	public function __construct(
		private string $baseUrl,
		private Session $session,
		private IPresenterFactory $presenterFactory,
		private IRouter $router,
		private Application $application,
		private IRequest $httpRequest,
		private User $user,
	) {}


	public function execute(TestPresenterRequest $testRequest, string $name): TestPresenterResult
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
		if ($applicationRequest->getParameter(Presenter::SIGNAL_KEY) && method_exists($presenter, 'isSignalProcessed')) {
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

		$result = new TestPresenterResult($this->router, $applicationRequest, $presenter, $response, $badRequestException, $name);

		return $result;
	}


	public function createRequest(string $url, string $presenterName): TestPresenterRequest
	{
		return new TestPresenterRequest($url, $presenterName, $this->session);
	}


	protected function createPresenter(TestPresenterRequest $request): IPresenter
	{
		$this->loginUser($request);
		$this->setupHttpRequest($request);
		$presenter = $this->presenterFactory->createPresenter($request->getPresenterName());
		if ($presenter instanceof Presenter) {
			$this->setupUIPresenter($presenter);
		}

		return $presenter;
	}


	protected function createApplicationRequest(TestPresenterRequest $testRequest): AppRequest
	{
		return new AppRequest(
			$testRequest->getPresenterName(),
			$testRequest->getPost() ? 'POST' : $testRequest->getMethodName(),
			$testRequest->getParameters(),
			$testRequest->getPost(),
			$testRequest->getFiles()
		);
	}


	protected function loginUser(TestPresenterRequest $request): void
	{
		$this->user->logout(true);
		$identity = $request->getIdentity();
		if ($identity) {
			$this->user->login($identity);
		}
	}


	protected function setupHttpRequest(TestPresenterRequest $request): void
	{
		$appRequest = $this->createApplicationRequest($request);
		\Closure::bind(function () use ($request) {
			/** @var Request $this */
			$this->headers = $request->getHeaders() + $this->headers;
			if ($request->isAjax()) {
				$this->headers['x-requested-with'] = 'XMLHttpRequest';
			} else {
				unset($this->headers['x-requested-with']);
			}
			$this->post = $request->getPost();
			$this->url = new UrlScript($request->getUrl());
			$this->method = ($request->getPost() || $request->getRawBody()) ? 'POST' : 'GET';
			$this->rawBodyCallback = [$request, 'getRawBody'];
		}, $this->httpRequest, Request::class)->__invoke();
	}


	protected function setupUIPresenter(Presenter $presenter): void
	{
		$presenter->autoCanonicalize = false;
		$presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;
	}
}
