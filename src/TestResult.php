<?php

declare(strict_types=1);

namespace Stepapo\RequestTester;

use Nette\Application\BadRequestException;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Tester\Assert;
use Tester\Dumper;


class TestResult
{
	private ?string $textResponseSource = null;


	public function __construct(
		private ?Response $response,
		private ?BadRequestException $badRequestException,
		public string $name,
	) {}


	public function getResponse(): Response
	{
		Assert::null($this->badRequestException, Dumper::color('red', $this->name));
		assert($this->response !== null);
		return $this->response;
	}


	public function getTextResponse(): TextResponse
	{
		$response = $this->getResponse();
		Assert::type(TextResponse::class, $response, Dumper::color('red', $this->name));
		assert($response instanceof TextResponse);
		return $response;
	}


	public function getTextResponseSource(): string
	{
		if (!$this->textResponseSource) {
			$source = $this->getTextResponse()->getSource();
			$this->textResponseSource = is_object($source) ? $source->__toString(true) : (string) $source;
			Assert::type('string', $this->textResponseSource, Dumper::color('red', $this->name));
		}
		return $this->textResponseSource;
	}


	public function getJsonResponse(): JsonResponse
	{
		$response = $this->getResponse();
		Assert::type(JsonResponse::class, $response, Dumper::color('red', $this->name));
		assert($response instanceof JsonResponse);
		return $response;
	}


	public function assertRenders(string|array|null $match = null): self
	{
		if (is_array($match)) {
			$m = implode(', ', $match);
			$match = '%A?%' . implode('%A?%', $match) . '%A?%';
		}
		assert(is_string($match) || $match === null);
		$source = $this->getTextResponseSource();
		if ($match !== null) {
			if (!Assert::isMatching($match, $source)) {
				[$pattern, $actual] = Assert::expandMatchingPatterns($match, $source);
				Assert::fail(Dumper::color('red', $this->name) . ': ' . Dumper::color('white', 'Should render') . ' %2 but does not', $actual, $m);
			}
		}
		return $this;
	}


	public function assertNotRenders(string|array $matches): self
	{
		if (is_string($matches)) {
			$matches = [$matches];
		}
		assert(is_array($matches));
		$source = $this->getTextResponseSource();
		foreach ($matches as $match) {
			assert(is_string($match));
			$m = $match;
			$match = "%A%$match%A%";
			if (Assert::isMatching($match, $source)) {
				[$pattern, $actual] = Assert::expandMatchingPatterns($match, $source);
				Assert::fail(Dumper::color('red', $this->name) . ': ' . Dumper::color('white', 'Should NOT render') . ' %2 but does', $actual, $m);
			}
		}
		return $this;
	}


	public function assertJson(?array $expected = null): self
	{
		$response = $this->getJsonResponse();
		if (func_num_args() !== 0) {
			Assert::equal($expected, $response->getPayload(), Dumper::color('red', $this->name));
		}
		return $this;
	}


	public function assertBadRequest(int $code = null): self
	{
		Assert::type(BadRequestException::class, $this->badRequestException, Dumper::color('red', $this->name));
		assert($this->badRequestException !== null);
		if ($code !== null) {
			Assert::same($code, $this->badRequestException->getHttpCode(), Dumper::color('red', $this->name));
		}
		return $this;
	}
}
