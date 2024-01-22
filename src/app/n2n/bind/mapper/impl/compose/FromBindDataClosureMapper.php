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
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\util\type\attrs\DataMap;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;

class FromBindDataClosureMapper extends SingleMapperAdapter {

	function __construct(private \Closure $closure) {
	}


	function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::type(Mapper::class));
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClosure($this->closure);

		$bindSource = $bindBoundary->unwarpBindSource();
		$bindContext = $bindBoundary->getBindContext();

		try {
			$bindData = new BindData(new DataMap(TypeConstraints::array()->validate($bindable->getValue())));
			$invoker->setClassParamObject(BindData::class, $bindData);
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new BindMismatchException(self::class . ' is not compatible with Bindable "'
					. $bindable->getPath()->toAbsoluteString() . '"', previous: $e);
		}

		$mapper = $invoker->invoke();
		assert($mapper instanceof Mapper);
		if (!$mapper->map(new BindBoundary($bindSource, $bindContext, [$bindable]), $magicContext)) {
			return false;
		}

		return true;
	}
}