<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\impl\MapperAdapter;

class BindablesClosureMapper extends MapperAdapter {

	private $closure;

	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closure);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool(true));

		return $invoker->invoke(null, null, [$bindBoundary->getBindables()]) ?? true;
	}
}