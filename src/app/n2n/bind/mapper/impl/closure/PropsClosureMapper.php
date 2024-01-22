<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;
use n2n\bind\plan\Bindable;
use n2n\bind\mapper\impl\MultiMapperAdapter;
use n2n\bind\mapper\impl\MultiMapMode;
use n2n\bind\plan\BindData;
use n2n\bind\build\impl\Bind;

class PropsClosureMapper extends MultiMapperAdapter {

	private $closure;

	public function __construct(\Closure $closure, private MultiMapMode $multiMapMode, private bool $bindDataMode = false) {
		parent::__construct($this->multiMapMode);
		$this->closure = $closure;
	}

	protected function mapMulti(array $bindables, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setMethod(new \ReflectionFunction($this->closure));
		$invoker->setReturnTypeConstraint(TypeConstraints::type(['array', BindData::class]));

		$bindablesMap = [];
		foreach ($bindables as $bindable) {
			$bindablesMap[$bindBoundary->pathToRelativeName($bindable->getPath())] = $bindable;
		}

		$valuesMap = array_map(fn (Bindable $b) => $b->getValue(), $bindablesMap);

		$returnValuesMap = $invoker->invoke(null, null,
				[$this->bindDataMode ? new BindData($valuesMap) : $valuesMap]);

		if ($returnValuesMap instanceof BindData) {
			$returnValuesMap = $returnValuesMap->toDataMap()->toArray();
		}

		foreach ($returnValuesMap as $relativeName => $value) {
			$bindable = $bindablesMap[$relativeName] ?? $bindBoundary->acquireBindableByRelativeName($relativeName);
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