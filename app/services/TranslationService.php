<?php

declare(strict_types=1);

namespace App\Service;

class TranslationService
{
	// single consonants, 'y' letter including
	private string $consonantPattern = '[b-df-hj-np-tv-z]';

	// ignore some special characters at the end of the word
	private string $endingNonLetters = '[,.?!;]*';

	// https://usefulenglish.ru/phonetics/practice-consonant-clusters
	// important: 3-lettered clusters must be first to find by preg_match as longest cluster
	/** @var array<string> $consonantClusters */
	private array $consonantClusters = [
		'scr',
		'shr',
		'spl',
		'spr',
		'str',
		'squ',
		'thr',
		'ch',
		'th',
		'pl',
		'pr',
		'bl',
		'br',
		'tr',
		'dr',
		'cl',
		'cr',
		'gl',
		'gr',
		'fl',
		'fr',
		'sk',
		'sc',
		'sl',
		'sm',
		'sn',
		'sp',
		'st',
		'sw',
		'tw',
		'dw',
		'qu',
		'gw',
		'gu',
	];

	/**
	 * @return array<int, string>
	 */
	private function splitString(string $text): array
	{
		$splitResult = preg_split('/[\s]+/', $text);

		return $splitResult === false ? [] : $splitResult;
	}

	private function translateWordStartsWithConsonant(string $word): string
	{
		// case in-sensitive
		// check non-letters at the end of word
		$consonantClusterPattern = sprintf(
			'/^(%s|%s)(.+?)(%s)$/i',
			implode('|', $this->consonantClusters),
			$this->consonantPattern,
			$this->endingNonLetters,
		);

		// try to find longest consonant cluster or single consonant
		if (preg_match($consonantClusterPattern, $word, $clusterMatches) === 1) {
			return sprintf('%s-%say%s', $clusterMatches[2], $clusterMatches[1], $clusterMatches[3] ?? '');
		}

		return '';
	}

	private function translateWordStartWithVowel(string $word): string
	{
		$vowelsMatchesPattern = sprintf('/^([a-z]+)(%s)$/i', $this->endingNonLetters);
		if (preg_match($vowelsMatchesPattern, $word, $vowelsMatches) === 1) {
			return sprintf('%s-ay%s', $vowelsMatches[1], $vowelsMatches[2] ?? '');
		}

		return '';
	}

	private function processEnglishWord(string $word): string
	{
		// try to find longest consonant cluster or single consonant
		$translation = $this->translateWordStartsWithConsonant($word);
		if ($translation !== '') {
			return $translation;
		}

		// vowels etc.
		$translation = $this->translateWordStartWithVowel($word);
		if ($translation !== '') {
			return $translation;
		}

		// keep unsupported word/character as it is
		return $word;
	}

	public function translateToPigLatin(string $englishString): string
	{
		$words = $this->splitString($englishString);

		foreach ($words as $arrayKey => $word) {
			$words[$arrayKey] = $this->processEnglishWord($word);
		}

		return sprintf('%s', implode(' ', $words));
	}
}
