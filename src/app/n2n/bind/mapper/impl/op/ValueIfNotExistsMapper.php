<?php
namespace n2n\bind\mapper\impl\op;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindContext;
use n2n\util\type\TypeConstraints;

class ValueIfNotExistsMapper extends SingleMapperAdapter {
	function __construct(private mixed $closureOrValue) {
		$this->nonExistingSkipped = false;
	}

	private function obtainValue(BindBoundary $bindBoundary, MagicContext $magicContext): mixed {
		if (!($this->closureOrValue instanceof \Closure)) {
			return $this->closureOrValue;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closureOrValue);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClassParamObject(BindContext::class, $bindBoundary->getBindContext());
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());
		return $invoker->invoke();
	}


	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		if ($bindable->doesExist()) {
			return true;
		}

		$bindable->setExist(true);
		$bindable->setValue( $this->obtainValue($bindBoundary, $magicContext));

		return true;
	}
}