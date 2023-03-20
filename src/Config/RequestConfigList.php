<?php declare(strict_types=1);

namespace Stepapo\UrlTester\Config;


class RequestConfigList
{
	/** @var RequestConfig[] */
	private array $requests;


	/** @return RequestConfig[] */
	public static function createFromArray(array $config): array
	{
		$list = new self();
		$list->addFromArray($config);
		return $list->requests;
	}


	public function addFromArray(array $config, ?array $defaultConfig = null, ?string $name = null): void
	{
		if (isset($config['identity']) && !is_array($config['identity'])) {
			$config['identity'] = [
				'id' => $config['identity'],
			];
		}
		if (isset($config['form']) && !is_array($config['form'])) {
			$config['form'] = [
				'name' => $config['form'],
			];
		}

		$newConfig = $defaultConfig ?: $config;
		$newConfig['name'] = ($defaultConfig && $defaultConfig['name'] ? $defaultConfig['name'] . ' ' : '') . $name;

		if (array_key_exists('url', $config)) {
			$newConfig['url'] = $config['url'];
		}
		if (array_key_exists('post', $config)) {
			$newConfig['post'] = $config['post'];
		}
		if (array_key_exists('identity', $config)) {
			if (array_key_exists('id', $config['identity'])) {
				$newConfig['identity']['id'] = $config['identity']['id'];
			}
			if (array_key_exists('roles', $config['identity'])) {
				$newConfig['identity']['roles'] = $config['identity']['roles'];
			}
			if (array_key_exists('username', $config['identity'])) {
				$newConfig['identity']['username'] = $config['identity']['username'];
			}
			if (array_key_exists('domain', $config['identity'])) {
				$newConfig['identity']['domain'] = $config['identity']['domain'];
			}
		}
		if (array_key_exists('form', $config)) {
			if (array_key_exists('name', $config['form'])) {
				$newConfig['form']['name'] = $config['form']['name'];
			}
			if (array_key_exists('post', $config['form'])) {
				$newConfig['form']['post'] = $config['form']['post'];
			}
		}
		if (array_key_exists('asserts', $config)) {
			$newConfig['asserts'] = $config['asserts'];
		}
		if (array_key_exists('refresh', $config)) {
			$newConfig['refresh'] = $config['refresh'];
		}
		if (array_key_exists('reset', $config)) {
			$newConfig['reset'] = $config['reset'];
		}
		if (array_key_exists('requests', $config)) {
			foreach ($config['requests'] as $n => $c) {
				$this->addFromArray($c, $newConfig, is_numeric($n) ? $c['name'] : $n);
			}
		} else {
			unset($newConfig['requests']);
			$this->requests[] = RequestConfig::createFromArray($newConfig);
		}
	}
}
