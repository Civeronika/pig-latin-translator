<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class HomepagePresenter extends Nette\Application\UI\Presenter
{
	protected function createComponentTranslationForm(): Form
	{
		$form = new Form();

		$english = $form->addTextArea('english', 'English text:')
			->setRequired('Enter your text')
			->setEmptyValue('Enter your text');

		$pigLatin = $form->addTextArea('pigLatin', 'Pig latin text:')
			->setDisabled()
			->setDefaultValue('Translated text will be here');

		$form->addSubmit('translate', 'Translate');

		$form->onSuccess[] = static function () use ($english, $pigLatin): void {
			/** @var string $englishText */
			$englishText = $english->getValue();
			$translatedText = sprintf('translated "%s"', $englishText);
			$pigLatin->setDefaultValue($translatedText);
		};

		return $form;
	}
}
