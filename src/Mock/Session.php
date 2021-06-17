<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Mock;

use Nette;


class Session extends Nette\Http\Session
{
	/** @var SessionSection[] */
	private array $sections = [];

	private bool $started = false;

	private bool $exists = false;

	private string $id;


	public function __construct()
	{
	}


	public function start(): void
	{
		$this->started = true;
	}


	public function isStarted(): bool
	{
		return $this->started;
	}


	public function close(): void
	{
		$this->started = false;
	}


	public function destroy(): void
	{
		$this->started = false;
	}


	public function exists(): bool
	{
		return $this->exists;
	}


	public function setFakeExists(bool $exists): void
	{
		$this->exists = $exists;
	}


	public function regenerateId(): void
	{
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function setFakeId($id)
	{
		$this->id = $id;
	}


	public function getSection(string $section, $class = SessionSection::class): Nette\Http\SessionSection
	{
		if (isset($this->sections[$section])) {
			return $this->sections[$section];
		}

		$sessionSection = parent::getSection($section, $class);
		assert($sessionSection instanceof SessionSection);

		return $this->sections[$section] = $sessionSection;
	}


	public function hasSection(string $section): bool
	{
		return isset($this->sections[$section]);
	}


	public function getIterator(): \Iterator
	{
		return new \ArrayIterator(array_keys($this->sections));
	}


	public function clean(): void
	{
	}


	public function setName(string $name)
	{
		return $this;
	}


	public function getName(): string
	{
		return '';
	}


	public function setOptions(array $options)
	{
		return $this;
	}


	public function getOptions(): array
	{
		return [];
	}


	public function setExpiration(?string $time)
	{
		return $this;
	}


	public function setCookieParameters(string $path, string $domain = null, bool $secure = null, string $samesite = null)
	{
		return $this;
	}


	public function getCookieParameters(): array
	{
		return [];
	}


	public function setSavePath(string $path)
	{
		return $this;
	}


	public function setHandler(\SessionHandlerInterface $handler)
	{
	}
}
