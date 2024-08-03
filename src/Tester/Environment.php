<?php

declare(strict_types=1);

namespace Stepapo\RequestTester\Tester;

use Tester\Assert;
use Tester\AssertException;
use Tester\CodeCoverage\Collector;
use Tester\Runner\Job;


class Environment extends \Tester\Environment
{
	private static int $obLevel;

	private static int $exitCode = 0;


	public static function setup(): void
	{
		self::setupErrors();
		self::setupColors();
		self::$obLevel = ob_get_level();

		class_exists(Job::class);
		class_exists(Dumper::class);
		class_exists(Assert::class);

		$annotations = self::getTestAnnotations();
		self::$checkAssertions = !isset($annotations['outputmatch']) && !isset($annotations['outputmatchfile']);

		if (getenv(self::COVERAGE) && getenv(self::COVERAGE_ENGINE)) {
			Collector::start(getenv(self::COVERAGE), getenv(self::COVERAGE_ENGINE));
		}

		if (getenv('TERMINAL_EMULATOR') === 'JetBrains-JediTerm') {
			Dumper::$maxPathSegments = -1;
			Dumper::$pathSeparator = '/';
		}
	}


	public static function setupErrors(): void
	{
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		ini_set('html_errors', '0');
		ini_set('log_errors', '0');

		set_exception_handler([__CLASS__, 'handleException']);

		set_error_handler(function (int $severity, string $message, string $file, int $line): ?bool {
			if (in_array($severity, [E_RECOVERABLE_ERROR, E_USER_ERROR], true) || ($severity & error_reporting()) === $severity) {
				self::handleException(new \ErrorException($message, 0, $severity, $file, $line));
			}
			return false;
		});

		register_shutdown_function(function (): void {
			Assert::$onFailure = [__CLASS__, 'handleException'];

			$error = error_get_last();
			register_shutdown_function(function () use ($error): void {
				if (in_array($error['type'] ?? null, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
					if (($error['type'] & error_reporting()) !== $error['type']) { // show fatal errors hidden by @shutup
						self::removeOutputBuffers();
						echo "\n", Dumper::color('white/red', "Fatal error: $error[message] in $error[file] on line $error[line]"), "\n";
					}
				} elseif (self::$checkAssertions && !Assert::$counter) {
					self::removeOutputBuffers();
					echo "\n", Dumper::color('white/red', 'Error: This test forgets to execute an assertion.'), "\n";
					self::exit(Job::CodeFail);
				} elseif (!getenv(parent::RUNNER) && self::$exitCode !== Job::CodeSkip) {
					echo "\n", (self::$exitCode ? Dumper::color('white/red', 'FAILURE') : Dumper::color('white/green', 'OK')), "\n";
				}
			});
		});
	}


	public static function handleException(\Throwable $e): void
	{
		self::removeOutputBuffers();
		self::$checkAssertions = false;
		echo Dumper::dumpException($e);
		self::exit($e instanceof AssertException ? Job::CodeFail : Job::CodeError);
	}


	private static function removeOutputBuffers(): void
	{
		while (ob_get_level() > self::$obLevel && @ob_end_flush()); // @ may be not removable
	}
}
