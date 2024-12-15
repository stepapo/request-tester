<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\KeyProperty;
use Stepapo\Utils\Attribute\Type;
use Stepapo\Utils\Config;


class Request extends Config
{
	#[KeyProperty] public string $name;
	public string $method = 'GET';
	public ?array $headers = null;
	public ?array $query = null;
	public string $path;
	public string|array|null $rawBody = null;
	public ?array $post = null;
	public bool $reset = true;
	public bool $refresh = false;
	#[Type(Identity::class)] public ?Identity $identity = null;
	#[Type(Form::class)] public ?Form $form = null;
	#[Type(Assert::class)] public ?Assert $asserts = null;
}

