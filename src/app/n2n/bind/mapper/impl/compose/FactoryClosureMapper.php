<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\mapper\MapResult;
use n2n\bind\plan\BindContext;

class FactoryClosureMapper extends MapperAdapter {

	function __construct(private \Closure $closure) {
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::namedType(Mapper::class, true));
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClassParamObject(BindContext::class, $bindBoundary->getBindContext());
		$invoker->setClosure($this->closure);

		$bindContext = $bindBoundary->getBindContext();

		$mapper = $invoker->invoke();
		if (null === $mapper) {
			return new MapResult();
		}

		assert($mapper instanceof Mapper);
		return $mapper->map(new BindBoundary($bindContext, $bindBoundary->getBindables()), $magicContext);
	}
}