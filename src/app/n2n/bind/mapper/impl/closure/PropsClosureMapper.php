<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\plan\BindBoundary;
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

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setMethod(new \ReflectionFunction($this->closure));
		$invoker->setReturnTypeConstraint(TypeConstraints::array());

		$bindablesMap = [];
		foreach ($bindBoundary->getBindables() as $bindable) {
			$bindablesMap[$bindBoundary->pathToRelativeName($bindable->getPath())] = $bindable;
		}

		$valuesMap = array_map(fn (Bindable $b) => $b->getValue(),
				array_filter($bindablesMap, fn (Bindable $b) => $b->doesExist()));

		if (($this->everyBindableMustExist === false && empty($valuesMap))
				|| ($this->everyBindableMustExist === true && count($valuesMap) < count($bindablesMap))) {
			return true;
		}

		$returnValuesMap = $invoker->invoke(null, null, [$valuesMap]);

		foreach ($returnValuesMap as $relativeName => $value) {
			$bindable = $bindablesMap[$relativeName] ?? $bindBoundary->acquireBindable($relativeName);
			$bindable->setValue($value);
			unset($bindablesMap[$relativeName]);
			$bindable->setExist(true);
		}

		foreach ($bindablesMap as $leftoverBindable) {
			$leftoverBindable->setExist(false);
		}

		return true;
	}
}