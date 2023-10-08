<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Service\TranslationService;
use Nette;
use Nette\Application\UI\Form;

final class HomepagePresenter extends Nette\Application\UI\Presenter
{
	protected TranslationService $translationService;

	public function __construct(TranslationService $translationService)
	{
		$this->translationService = $translationService;

		parent::__construct();
	}

	protected function createComponentTranslationForm(): Form
	{
		$form = new Form();

		$english = $form->addTextArea('english', 'English text:')
			->setRequired('Enter your text')
			->setEmptyValue('Enter your text');

		$pigLatin = $form->addTextArea('pigLatin', 'Pig Latin text:')
			->setDisabled()
			->setDefaultValue('Translated text will be here');

		$form->addSubmit('translate', 'Translate');

		$translationService = $this->translationService;
		$form->onSuccess[] = static function () use ($english, $pigLatin, $translationService): void {
			/** @var string $englishText */
			$englishText = $english->getValue();
			$translatedText = $translationService->translateToPigLatin($englishText);
			$pigLatin->setDefaultValue($translatedText);
		};

		return $form;
	}
}
