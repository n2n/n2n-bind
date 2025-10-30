<?php

namespace n2n\bind\mapper\impl\op;

use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\bind\mapper\impl\mod\BindablesFilter;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\plan\BindContext;

class DoIfMapper extends MapperAdapter  {

	private bool $cascaded = false;

	public function __construct(private \Closure|bool $closureOrBool, private bool $abort = false,
			private bool $skipNextMappers = false, private ?bool $chLogical = null, private ?bool $chExists = null) {
	}

	private function obtainCondition(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		if (is_bool($this->closureOrBool)) {
			return $this->closureOrBool;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClosure($this->closureOrBool);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClassParamObject(BindContext::class, $bindBoundary->getBindContext());
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());
		return $invoker->invoke();
	}

	function isCascaded(): bool {
		return $this->cascaded;
	}

	/**
	 * If true the changes will be cascaded to its descendants.
	 * @param bool $cascaded
	 * @return $this
	 */
	function setCascaded(bool $cascaded): static {
		$this->cascaded = $cascaded;
		return $this;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		if (!$this->obtainCondition($bindBoundary, $magicContext)) {
			return new MapResult();
		}

		if ($this->chLogical === null && $this->chExists === null) {
			return new MapResult(!$this->abort, $this->skipNextMappers);
		}

		foreach ($bindBoundary->getBindables() as $bindable) {
			$this->doBindableModifications($bindable, $bindBoundary);
		}

		return new MapResult(!$this->abort, $this->skipNextMappers);
	}

	private function doBindableModifications(Bindable $bindable, BindBoundary $bindBoundary): void {


		$bindables = [$bindable];
		if ($this->cascaded) {
			$bindablesFilter = new BindablesFilter($bindBoundary->unwarpBindInstance());
			array_push($bindables, ...array_values($bindablesFilter->descendantsOf($bindable->getPath())));
		}

		foreach ($bindables as $bindable) {
			if ($this->chLogical !== null) {
				$bindable->setLogical($this->chLogical);
			}

			if ($this->chExists !== null) {
				$bindable->setExist($this->chExists);
			}
		}
	}
}