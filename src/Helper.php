<?php declare(strict_types=1);

namespace Stepapo\UrlTester;

use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Utils\DateTime;
use Stepapo\UrlTester\PresenterTester\TestPresenterRequest;
use Stepapo\UrlTester\PresenterTester\TestPresenterResult;
use Tester\Expect;


class Helper
{
	public function __construct(
		private UrlTester $urlTester,
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
			$result = $this->urlTester->execute($request, $result->name);
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
		$httpRequest = new HttpRequest(new UrlScript($url, '/'));

		$params = $this->router->match($httpRequest);

		$presenter = $params[Presenter::PRESENTER_KEY] ?? null;
		unset($params[Presenter::PRESENTER_KEY]);

		$request = $this->urlTester->createRequest($presenter)
			->withParameters($params);

		return $request;
	}


	public function prepareValues(array $values, bool $toString = false): array
	{
		$return = [];
		foreach ($values as $key => $value) {
			if (is_array($value)) {
				$return[$key] = $this->prepareValues($value, $toString);
			} else {
				$return[$key] = $this->prepareValue($value, $toString);
			}
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
