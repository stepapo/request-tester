<?php declare(strict_types=1);

namespace Stepapo\UrlTester\PresenterTester;

use Nette\Forms\Controls\CsrfProtection;
use Nette\Http\Session;
use Nette\Security\IIdentity;


class TestPresenterRequest
{
	private string $methodName = 'GET';

	private array $headers = [];

	private array $parameters = [];

	private array $post = [];

	private ?string $rawBody = null;

	private array $files = [];

	private bool $ajax = false;

	private ?IIdentity $identity = null;


	public function __construct(
		private string $presenterName,
		private Session $session
	) {
		$this->session->setFakeId('stepapo.id');
		$this->session->getSection(CsrfProtection::class)->token = 'stepapo.token';
	}


	public function getMethodName(): string
	{
		return $this->methodName;
	}


	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function getPresenterName(): string
	{
		return $this->presenterName;
	}


	public function getParameters(): array
	{
		return $this->parameters + ['action' => 'default'];
	}


	public function getPost(): array
	{
		return $this->post;
	}


	public function getRawBody(): ?string
	{
		return $this->rawBody;
	}


	public function getFiles(): array
	{
		return $this->files;
	}


	public function isAjax(): bool
	{
		return $this->ajax;
	}


	public function getIdentity(): ?IIdentity
	{
		return $this->identity;
	}


	public function withMethod(string $methodName): TestPresenterRequest
	{
		$request = clone $this;
		$request->methodName = $methodName;

		return $request;
	}


	public function withForm(string $formName, array $post, array $files = [], bool $withProtection = true): TestPresenterRequest
	{
		$request = clone $this;
		$request->parameters['do'] = "$formName-submit";
		if ($withProtection) {
			$token = 'abcdefghij' . base64_encode(sha1(('stepapo.token' ^ $this->session->getId()) . 'abcdefghij', true));
			$post = $post + ['_token_' => $token];
		}
		$request->post = $post;
		$request->files = $files;

		return $request;
	}


	public function withRawBody(string $rawBody): TestPresenterRequest
	{
		$request = clone $this;
		$request->rawBody = $rawBody;

		return $request;
	}


	public function withHeaders(array $headers): TestPresenterRequest
	{
		$request = clone $this;
		$request->headers = array_change_key_case($headers, CASE_LOWER) + $request->headers;

		return $request;
	}


	public function withAjax(bool $enable = true): TestPresenterRequest
	{
		$request = clone $this;
		$request->ajax = $enable;

		return $request;
	}


	public function withParameters(array $parameters): TestPresenterRequest
	{
		$request = clone $this;
		$request->parameters = $parameters + $this->parameters;

		return $request;
	}


	public function withPost(array $post): TestPresenterRequest
	{
		$request = clone $this;
		$request->post = $post + $this->post;

		return $request;
	}


	public function withFiles(array $files): TestPresenterRequest
	{
		$request = clone $this;
		$request->files = $files + $this->files;

		return $request;
	}


	public function withIdentity(IIdentity $identity = null): TestPresenterRequest
	{
		$request = clone $this;
		$request->identity = $identity;

		return $request;
	}

}
