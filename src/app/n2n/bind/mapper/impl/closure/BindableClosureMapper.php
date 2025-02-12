<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\MapResult;

class BindableClosureMapper extends SingleMapperAdapter {

	public function __construct(private \Closure $closure, private bool $nullSkipped) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		if ($this->nullSkipped && $bindable->getValue() === null) {
			return new MapResult();
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closure);
		$invoker->setReturnTypeConstraint(TypeConstraints::type(['bool', MapResult::class, 'null']));

		return MapResult::fromArg($invoker->invoke(null, null, [$bindable]));
	}
}