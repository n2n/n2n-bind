<?php

namespace n2n\bind\mapper\impl\op;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\impl\MagicMethodInvoker;

class DoIfSingleClosureMapper extends SingleMapperAdapter {

	private bool $valueAsFirstArg = true;

	public function __construct(private \Closure $closure, private bool $abort = false,
			private bool $skipNextMappers = false, private ?bool $chLogical = null) {
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

		if ($this->chLogical !== null) {
			$bindable->setLogical($this->chLogical);
		}

		return new MapResult(!$this->abort, $this->skipNextMappers);
	}
}