<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\BindData;

class ValueClosureMapper extends SingleMapperAdapter {

	private $closure;

	public function __construct(\Closure $closure, private bool $nullSkipped) {
		$this->closure = $closure;
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $bindable->getValue();
		if ($this->nullSkipped && $value === null) {
			return true;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClassParamObject(Bindable::class, $bindable);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setReturnTypeConstraint(TypeConstraints::mixed());

		$returnValue = $invoker->invoke(null, $this->closure, [$value]);

		if ($returnValue instanceof BindData) {
			$returnValue = $returnValue->toDataMap()->toArray();
		}

		$bindable->setValue($returnValue);

		return true;
	}
}