<?php

namespace n2n\bind\mapper\impl\op;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\bind\mapper\impl\mod\BindablesFilter;

class DoIfSingleClosureMapper extends SingleMapperAdapter {

	private bool $valueAsFirstArg = true;
	private bool $cascaded = false;

	public function __construct(private \Closure $closure, private bool $abort = false,
			private bool $skipNextMappers = false, private ?bool $chLogical = null, private ?bool $chExists = null) {
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

	public function isValueAsFirstArg(): bool {
		return $this->valueAsFirstArg;
	}

	public function setValueAsFirstArg(bool $valueAsFirstArg): static {
		$this->valueAsFirstArg = $valueAsFirstArg;
		return $this;
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$value = $bindable->getValue();

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClassParamObject(Bindable::class, $bindable);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());

		$returnValue = $invoker->invoke(null, $this->closure,
				$this->valueAsFirstArg ? [$value] : []);
		if (!$returnValue) {
			return new MapResult();
		}

		$this->doBindableModifications($bindable, $bindBoundary);

		return new MapResult(!$this->abort, $this->skipNextMappers);
	}

	private function doBindableModifications(Bindable $bindable, BindBoundary $bindBoundary): void {
		if ($this->chLogical === null && $this->chExists === null) {
			return;
		}

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