<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindPlan;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\plan\BindData;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\TypeConstraints;

class FromBindDataClosureMapper implements Mapper {

	function __construct(private \Closure $closure) {
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::type(Mapper::class));
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClosure($this->closure);

		$bindSource = $bindBoundary->unwarpBindSource();
		$bindContext = $bindBoundary->getBindContext();

		foreach ($bindBoundary->getPaths() as $path) {
			try {
				$invoker->setClassParamObject(BindData::class,
						$bindSource->getRawBindData($path, true));
			} catch (UnresolvableBindableException $e) {
				throw new BindMismatchException(self::class . ' is not compatible with Bindable "'
						. $path->toAbsoluteString() . '"', previous: $e);
			}

			$bindable = $bindBoundary->getBindable($path);

			$mapper = $invoker->invoke();
			assert($mapper instanceof Mapper);
			if (!$mapper->map(new BindBoundary($bindSource, $bindContext,
					$bindable === null ? [] : [$bindable],
					[$path]), $magicContext)) {
				return false;
			}
		}

		return true;
	}
}