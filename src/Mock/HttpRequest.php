<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Mock;

use Nette\Http\Request;


class HttpRequest extends Request
{
	public function isSameSite(): bool
	{
		return true;
	}
}
