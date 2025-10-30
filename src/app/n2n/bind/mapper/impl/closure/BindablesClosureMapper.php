<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\mapper\MapResult;

class BindablesClosureMapper extends MapperAdapter {

	private $closure;

	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closure);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool(true));

		return MapResult::fromArg($invoker->invoke(null, null, [$bindBoundary->getBindables()]));
	}
}