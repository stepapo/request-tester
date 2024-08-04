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
	public string $path;
	public ?array $post = null;
	public bool $reset = true;
	public bool $refresh = false;
	#[Type(IdentityConfig::class)] public IdentityConfig|array|null $identity = null;
	#[Type(FormConfig::class)] public FormConfig|array|null $form = null;
	#[Type(AssertConfig::class)] public AssertConfig|array|null $asserts = null;
}

