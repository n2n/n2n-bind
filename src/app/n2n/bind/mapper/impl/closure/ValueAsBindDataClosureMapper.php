<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindData;
use n2n\util\type\TypeConstraints;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\type\attrs\DataMap;
use n2n\bind\plan\Bindable;
use n2n\bind\mapper\MapResult;

class ValueAsBindDataClosureMapper extends SingleMapperAdapter {

	function __construct(private \Closure $closure) {
	}

	function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$value = $this->readSafeValue($bindable, TypeConstraints::array());
		$binData = new BindData(new DataMap($value));

		if ($value === null) {
			return new MapResult();
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::mixed());

		$returnValue = $invoker->invoke(null, $this->closure, [$binData]);

		$bindable->setValue($returnValue);

		return new MapResult();
	}
}