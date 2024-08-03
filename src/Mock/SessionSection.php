<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Mock;

use Nette;


class SessionSection extends Nette\Http\SessionSection
{

	private $data = [];


	public function __construct(Nette\Http\Session $session, $name)
	{
		parent::__construct($session, $name);
	}


	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->data);
	}


	public function __set(string $name, $value): void
	{
		$this->data[$name] = $value;
	}


	public function &__get(string $name): mixed
	{
		if ($this->warnOnUndefined && !array_key_exists($name, $this->data)) {
			trigger_error("The variable '$name' does not exist in session section", E_USER_NOTICE);
		}

		return $this->data[$name];
	}


	public function __isset(string $name): bool
	{
		return isset($this->data[$name]);
	}


	public function __unset(string $name): void
	{
		unset($this->data[$name]);
	}


	public function setExpiration(?string $time, string|array|null $variables = null): static
	{
		return $this;
	}


	public function removeExpiration(string|array|null $variables = null): void
	{
	}


	public function remove(string|array|null $name = null): void
	{
		$this->data = [];
	}

}
