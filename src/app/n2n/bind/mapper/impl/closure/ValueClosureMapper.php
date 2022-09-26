<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;

class ValueClosureMapper extends SingleMapperAdapter {

	private $closure;

	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $bindable->getValue();

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setMethod(new \ReflectionFunction($this->closure));
		$invoker->setReturnTypeConstraint(TypeConstraints::mixed());

		$returnValue = $invoker->invoke(null, null,[$value]);

		$bindable->setValue($returnValue);

		return true;
	}
}