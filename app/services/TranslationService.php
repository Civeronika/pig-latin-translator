<?php

declare(strict_types=1);

namespace App\Service;

class TranslationService
{
	public function translateToPigLatin(string $englishString): string
	{
		return sprintf('translated "%s"', $englishString);
	}
}
