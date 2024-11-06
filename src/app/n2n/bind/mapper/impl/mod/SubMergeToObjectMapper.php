<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\bind\build\impl\target\ObjectBindableWriteProcess;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;

class SubMergeToObjectMapper extends SingleMapperAdapter {


	function __construct(private \Closure $objCallbackClosure) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$mmi = new MagicMethodInvoker($magicContext);
		$mmi->setClosure($this->objCallbackClosure);
		$mmi->setReturnTypeConstraint(TypeConstraints::namedType('object', false));
		$obj = $mmi->invoke();

		$bindablesFilter = new BindablesFilter($bindBoundary->unwarpBindInstance());
		$descendantBindables = $bindablesFilter->descendantsOf($bindable->getPath());

		$objectBindableWriteProcess = new ObjectBindableWriteProcess($descendantBindables);
		try {
			$objectBindableWriteProcess->process($obj);
		} catch (BindTargetException $e) {
			throw new BindMismatchException('Could not map sub bindables of ' . $bindable->getPath()
					. ' to object of type ' . get_class($obj), previous: $e);
		}

		$bindable->setValue($obj);
		foreach ($descendantBindables as $descendantBindable) {
			$descendantBindable->setExist(false);
		}

		return true;
	}
}