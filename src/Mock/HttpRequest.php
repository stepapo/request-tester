<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Mock;

use Nette\Http\Request;


class HttpRequest extends Request
{
	private array $headers = [];

	private ?string $body;


	public function setRawBody(?string $body)
	{
		$this->body = $body;
	}


	public function getRawBody(): ?string
	{
		return $this->body ?? parent::getRawBody();
	}


	public function setHeader(string $name, string $value)
	{
		$this->headers[$name] = $value;
	}


	public function getHeader(string $header): ?string
	{
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		}
		return parent::getHeader($header);
	}


	public function getHeaders(): array
	{
		return array_merge(parent::getHeaders(), $this->headers);
	}


	public function isSameSite(): bool
	{
		return true;
	}
}
