<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\impl\Validators;
use n2n\validation\validator\Validator;
use n2n\bind\plan\BindContext;
use n2n\bind\mapper\impl\SingleMapperAdapter;

class CleanStringMapper extends SingleMapperAdapter {

	function __construct(private bool $mandatory, private ?int $minlength, private ?int $maxlength) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::string(true));

		if ($value !== null) {
			$bindable->setValue(StringUtils::clean($value));
		}

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators() {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		if ($this->minlength !== null) {
			$validators[] = Validators::minlength($this->minlength);
		}

		if ($this->maxlength !== null) {
			$validators[] = Validators::maxlength($this->maxlength);
		}

		return $validators;
	}
}