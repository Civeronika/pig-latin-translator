<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\TranslationService;
use Nette\DI\Container;
use Tester;
use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/bootstrap.php';

$container = require __DIR__ . '/../app/bootstrap.php';

// disable Tracy to avoid error handler problems
Debugger::enable(false);

/**
 * TEST: Basic translation service test
 *
 * @testCase
 * @phpVersion > 7.4
 */
class TranslationServiceTest extends Tester\TestCase
{
	/** @property TranslationService $translationService */
	protected TranslationService $translationService;

	/**
	 * @return array<string,string>
	 */
	private function getConsonantTranslationMap(): array
	{
		return [
			'beast' => 'east-bay',
			'dough' => 'ough-day',
			'happy' => 'appy-hay',
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function getConsonantClusterTranslationMap(): array
	{
		return [
			'question' => 'estion-quay',
			'star' => 'ar-stay',
			'three' => 'ee-thray',
			'spray' => 'ay-spray',
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function getVowelsTranslationMap(): array
	{
		return [
			'apple' => 'apple-ay',
			'egg' => 'egg-ay',
			'and' => 'and-ay',
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function getOthersTranslationMap(): array
	{
		return [
			'%af5' => '%af5',
			'hello!' => 'ello-hay!',
			'nothing...' => 'othing-nay...',
			'Hi pig!' => 'i-Hay ig-pay!',
		];
	}

	public function __construct(Container $container)
	{
		/** @var ?TranslationService $service */
		$service = $container->getByType('App\Service\TranslationService');
		if (!isset($service)) {
			throw new \Nette\DI\MissingServiceException();
		}
		$this->translationService = $service;
	}

	/**
	 * @param  array<string,string> $map
	 * @return void
	 */
	private function assertByMap(array $map): void
	{
		foreach ($map as $input => $expected) {
			Assert::same(
				$expected,
				$this->translationService->translateToPigLatin($input),
			);
		}
	}

	public function testConsonant(): void
	{
		$this->assertByMap($this->getConsonantTranslationMap());
	}

	public function testConsonantCluster(): void
	{
		$this->assertByMap($this->getConsonantClusterTranslationMap());
	}

	public function testConsonantVowel(): void
	{
		$this->assertByMap($this->getVowelsTranslationMap());
	}

	public function testOthersMap(): void
	{
		$this->assertByMap($this->getOthersTranslationMap());
	}
}

// Run testing methods
(new TranslationServiceTest($container))->run();
