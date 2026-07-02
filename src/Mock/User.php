<?php declare(strict_types=1);

namespace Stepapo\RequestTester\Mock;

use Nette\Security\Authenticator;
use Nette\Security\Authorizator;
use Nette\Security\UserStorage;


class User extends \Nette\Security\User
{
	public function __construct(
		UserStorage $legacyStorage,
		?Authenticator $authenticator = null,
		?Authorizator $authorizator = null,
	) {
		parent::__construct($legacyStorage, $authenticator, $authorizator);
	}
}
