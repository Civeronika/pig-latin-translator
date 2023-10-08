# Pig Latin translator
Pig Latin translator is created as PHP\Nette test project. 

Translation is implemented as simple form at the homepage. 

English strings are translated to pig latin strings:
- words starting with *consonants*:
- - search for the longest consonant cluster and process as (cluster)someting => someting-(cluster)ay
- words starting with *vowels*: 
- - (vowel)word => (vowel)word-ay
- keeps unsupported words as they are

String processing details:
- string processing is case insensitive
- input text is splitted to words by whitespaces, output removes additional empty spaces
- ignore, but keep standard sentences characters at the end of the words, as .,!?
- consonant clusters specified by [Practice consonant clusters](https://usefulenglish.ru/phonetics/practice-consonant-clusters)


---

## Requirements
PHP 7.4 or higher.

## Nette version
This project uses Nette 2.4 version. 


---

## Code style
Code style is checked by [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).
`./vendor/bin/phpcs --standard=./phpcs.xml`

## PHP code standards
PHP ode standards are checked by [PHPStan](https://phpstan.org/).
`vendor/bin/phpstan analyse --level=max app tests`

## Automated tests
Automated service output tests by [Nette Tester](https://tester.nette.org/)
`vendor/bin/tester tests`
