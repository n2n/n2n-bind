<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindBoundary;

class BindableClosureMapper extends SingleMapperAdapter {

	public function __construct(private \Closure $closure, private bool $nullSkipped) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		if ($this->nullSkipped && $bindable->getValue() === null) {
			return true;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closure);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool(true));

		return $invoker->invoke(null, null, [$bindable]) ?? true;
	}
}