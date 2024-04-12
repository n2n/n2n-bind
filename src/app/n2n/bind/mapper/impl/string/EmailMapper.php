<?php
namespace n2n\bind\mapper\impl\string;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\impl\Validators;
use n2n\util\StringUtils;
use n2n\validation\validator\Validator;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\MapperUtils;

class EmailMapper extends SingleMapperAdapter {
	function __construct(private bool $mandatory) {

	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::string(true));

		if ($value !== null) {
			$bindable->setValue(mb_strtolower(StringUtils::clean($value)));
		}

		MapperUtils::validate([$bindable], $this->createValidators(), $bindBoundary->getBindContext(), $magicContext);

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

		$validators[] = Validators::email();

		return $validators;
	}
}