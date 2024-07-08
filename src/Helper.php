<?php declare(strict_types=1);

namespace Stepapo\RequestTester;

use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Utils\DateTime;
use Stepapo\RequestTester\PresenterTester\TestPresenterRequest;
use Stepapo\RequestTester\PresenterTester\TestPresenterResult;
use Tester\Expect;


class Helper
{
	public function __construct(
		private RequestTester $requestTester,
		private Router $router,
	) {}


	public function getFinalResult(TestPresenterResult $result, ?IIdentity $identity): TestPresenterResult
	{
		$response = $result->getResponse();
		if ($response instanceof RedirectResponse) {
			$request = $this->createRequestFromRedirectResponse($response);
			if ($identity) {
				$request = $request->withIdentity($identity);
			}
			$result = $this->requestTester->execute($request, $result->name);
			return $this->getFinalResult($result, $identity);
		}

		return $result;
	}


	public function createRequestFromRedirectResponse(RedirectResponse $response): TestPresenterRequest
	{
		return $this->createRequestFromUrl($response->getUrl());
	}


	public function createRequestFromUrl(string $url): TestPresenterRequest
	{
		$httpRequest = new Request(new UrlScript($url, '/'));

		$params = $this->router->match($httpRequest);

		$presenter = $params[Presenter::PresenterKey] ?? null;
		unset($params[Presenter::PresenterKey]);

		$request = $this->requestTester->createRequest($presenter)
			->withParameters($params);

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


	public function prepareValue($wildcard, bool $toString = false)
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
