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

	public function __construct(\Closure $closure, private ?bool $everyBindableMustExist) {
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

		$valuesMap = array_map(fn (Bindable $b) => $b->getValue(),
				array_filter($bindablesMap, fn (Bindable $b) => $b->doesExist()));

		if (($this->everyBindableMustExist === false && empty($valuesMap))
				|| ($this->everyBindableMustExist === true && count($valuesMap) < count($bindablesMap))) {
			return true;
		}

		$returnValuesMap = $invoker->invoke(null, null, [$valuesMap]);

		foreach ($returnValuesMap as $name => $value) {
			$bindable = $bindablesMap[$name] ?? $bindableBoundary->acquireBindable($name);
			$bindable->setValue($value);
			unset($bindablesMap[$name]);
			$bindable->setExist(true);
		}

		foreach ($bindablesMap as $leftoverBindable) {
			$leftoverBindable->setExist(false);
		}

		return true;
	}
}