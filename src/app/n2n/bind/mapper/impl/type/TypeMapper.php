<?php

namespace n2n\bind\mapper\impl\type;

use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\impl\Validators;
use n2n\validation\validator\Validator;
use n2n\bind\plan\BindContext;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\type\TypeConstraint;

class TypeMapper extends SingleMapperAdapter {

	function __construct(private TypeConstraint $typeConstraint) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$bindable->setValue($this->readSafeValue($bindable, $this->typeConstraint));

		return true;
	}
}