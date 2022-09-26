<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\plan\BindableBoundary;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;
use n2n\bind\plan\Bindable;

class PropsClosureMapper extends MapperAdapter {

	private $closure;

	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	function map(BindableBoundary $bindableBoundary, BindContext $bindContext, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setMethod(new \ReflectionFunction($this->closure));
		$invoker->setReturnTypeConstraint(TypeConstraints::array());

		$bindablesMap = [];
		foreach ($bindableBoundary->getBindables() as $bindable) {
			$bindablesMap[(string) $bindable->getName()] = $bindable;
		}

		$valuesMap = array_map(fn (Bindable $b) => $b->getValue(), $bindablesMap);
		$returnValuesMap = $invoker->invoke(null, null, [$valuesMap]);

		foreach ($returnValuesMap as $name => $value) {
			if (isset($bindablesMap[$name])) {
				$bindable = $bindablesMap[$name];
				unset($bindablesMap[$name]);
			} else {
				$bindable = $bindableBoundary->acquireBindable($name);
				$bindable->setExist(true);
			}

			$bindable->setValue($value);
		}

		foreach ($bindablesMap as $leftoverBindable) {
			$leftoverBindable->setExist(false);
		}

		return true;
	}
}