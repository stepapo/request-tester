<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Mock;

use Nette\Http\Request;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;


/**
 * HttpRequest provides access scheme for request sent via HTTP.
 *
 * @property UrlScript $url
 * @property array $query
 * @property array $post
 * @property array $files
 * @property array $cookies
 * @property string $method
 * @property array $headers
 * @property UrlImmutable|null $referer
 * @property bool $secured
 * @property bool $ajax
 * @property string|null $remoteAddress
 * @property string|null $remoteHost
 * @property string|null $rawBody
 */
class HttpRequest extends Request
{
	public  array $headers;
	public  ?\Closure $rawBodyCallback;


	/** copy from Nette\Http\Request needed to override readonly */
	public function __construct(
		public UrlScript $url,
		public array $post = [],
		private readonly  array $files = [],
		private readonly array $cookies = [],
		array $headers = [],
		public string $method = 'GET',
		private readonly ?string $remoteAddress = null,
		private readonly ?string $remoteHost = null,
		?callable $rawBodyCallback = null,
	) {
		parent::__construct($url, $post, $files, $cookies, $headers, $method, $remoteAddress, $remoteHost, $rawBodyCallback);
		$this->headers = array_change_key_case($headers, CASE_LOWER);
		$this->rawBodyCallback = $rawBodyCallback ? $rawBodyCallback(...) : null;
	}


	public function isSameSite(): bool
	{
		return true;
	}


	/** copy from Nette\Http\Request needed to override readonly */
	public function getQuery(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->url->getQueryParameters();
		}

		return $this->url->getQueryParameter($key);
	}


	/** copy from Nette\Http\Request needed to override readonly */
	public function getPost(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->post;
		}

		return $this->post[$key] ?? null;
	}


	/** copy from Nette\Http\Request needed to override readonly */
	public function getRawBody(): ?string
	{
		return $this->rawBodyCallback ? ($this->rawBodyCallback)() : null;
	}
}
