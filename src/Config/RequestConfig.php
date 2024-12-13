<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Config;

use Stepapo\Utils\Attribute\KeyProperty;
use Stepapo\Utils\Attribute\Type;
use Stepapo\Utils\Schematic;


class RequestConfig extends Schematic
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
	#[Type(IdentityConfig::class)] public ?IdentityConfig $identity = null;
	#[Type(FormConfig::class)] public ?FormConfig $form = null;
	#[Type(AssertConfig::class)] public ?AssertConfig $asserts = null;
}

