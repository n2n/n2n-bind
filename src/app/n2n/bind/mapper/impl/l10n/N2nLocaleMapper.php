<?php

namespace n2n\bind\mapper\impl\l10n;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\l10n\N2nLocale;
use n2n\util\type\ArgUtils;
use n2n\validation\lang\ValidationMessages;
use n2n\bind\err\BindMismatchException;
use n2n\l10n\IllegalN2nLocaleFormatException;

class N2nLocaleMapper extends SingleMapperAdapter {
	function __construct(private readonly bool $mandatory, private readonly ?array $allowedN2nLocales = null) {
		ArgUtils::valArray($allowedN2nLocales, N2nLocale::class, true);
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		try {
			$value = N2nLocale::build($this->readSafeValue($bindable, TypeConstraints::string(true)));
		} catch (IllegalN2nLocaleFormatException $e) {
			throw new BindMismatchException($e);
		}

		if ($value !== null) {
			$bindable->setValue($value);
		}

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators(): array {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		if ($this->allowedN2nLocales) {
			$validators[] = Validators::valueClosure(function ($n2nLocale) {
				if (in_array($n2nLocale, $this->allowedN2nLocales)) {
					return true;
				}

				return ValidationMessages::enum(array_map(fn (N2nLocale $l) => $l->getId(), $this->allowedN2nLocales));
			});
		}

		return $validators;
	}
}