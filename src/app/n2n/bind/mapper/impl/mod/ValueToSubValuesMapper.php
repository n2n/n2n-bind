<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\bind\plan\Bindable;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\magic\impl\MagicMethodInvoker;

class ValueToSubValuesMapper extends SingleMapperAdapter {

	public function __construct(private \Closure|array $subValuesClosureOrArray, private bool $chExistToFalse = false) {

	}

	private function obtainSubValues(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): array {
		if (is_array($this->subValuesClosureOrArray)) {
			return $this->subValuesClosureOrArray;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->subValuesClosureOrArray);
		$invoker->setClassParamObject(Bindable::class, $bindable);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClassParamObject(BindContext::class, $bindBoundary->getBindContext());
		$invoker->setReturnTypeConstraint(TypeConstraints::array());
		return $invoker->invoke(firstArgs: [$bindable->getValue()]);
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$values = $this->obtainSubValues($bindable, $bindBoundary, $magicContext);

		$bindContext = $bindBoundary->getBindContext();
		foreach ($values as $name => $value) {
			$subBindable = $bindContext->acquireBindableByAbsoluteName($bindable->getPath()->ext($name));
			$subBindable->setValue($value);
			$subBindable->setExist(true);
		}

		if ($this->chExistToFalse) {
			$bindable->setExist(false);
		}

		return true;
	}

}