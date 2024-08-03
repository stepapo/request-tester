<?php

declare(strict_types=1);

namespace Stepapo\RequestTester;

use Nette\Forms\Controls\CsrfProtection;
use Nette\Http\Session;
use Nette\Security\IIdentity;


class TestRequest
{
	public string $methodName = 'GET';
	public array $parameters = [];
	public array $post = [];
	public ?IIdentity $identity = null;


	public function __construct(
		public string $url,
		public string $presenterName,
		private Session $session,
	)
	{
//		$this->session->setFakeId('stepapo.id');
		$this->session->getSection(CsrfProtection::class)->token = 'stepapo.token';
	}


	public function setMethod(string $methodName): self
	{
		$this->methodName = $methodName;
		return $this;
	}


	public function setForm(string $formName, array $post, bool $withProtection = true): self
	{
		$this->parameters['do'] = "$formName-submit";
		if ($withProtection) {
			$token = 'abcdefghij' . base64_encode(sha1(('stepapo.token' ^ $this->session->getId()) . 'abcdefghij', true));
			$post = $post + ['_token_' => $token];
		}
		$this->post = $post;
		return $this;
	}


	public function setParameters(array $parameters): self
	{
		$this->parameters = $parameters + $this->parameters;
		return $this;
	}


	public function setPost(array $post): self
	{
		$this->post = $post + $this->post;
		return $this;
	}


	public function setIdentity(IIdentity $identity = null): self
	{
		$this->identity = $identity;
		return $this;
	}
}
