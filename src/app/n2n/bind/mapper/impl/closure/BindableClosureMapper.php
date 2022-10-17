<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\reflection\magic\MagicMethodInvoker;

class BindableClosureMapper extends SingleMapperAdapter {

	private $closure;

	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closure);
		$invoker->setReturnTypeConstraint(TypeConstraints::mixed());

		$invoker->invoke(null, null, [$bindable]);

		return true;
	}
}