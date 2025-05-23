<?php

namespace n2n\bind\mapper\impl\op;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\bind\mapper\Mapper;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\BindContext;

class MustExistIfMapper implements Mapper {

	public function __construct(private \Closure|bool $closureOrBool, private bool $elseChExistToFalse = false) {
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		if (is_bool($this->closureOrBool)) {
			if ($this->closureOrBool) {
				$this->mustExist($bindBoundary);
			} else {
				$this->else($bindBoundary);
			}
			return new MapResult();
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$invoker->setClassParamObject(BindContext::class, $bindBoundary->getBindContext());
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());
		$invoker->setClosure($this->closureOrBool);

		if ($invoker->invoke()) {
			$this->mustExist($bindBoundary);
		} else {
			$this->else($bindBoundary);
		}

		return new MapResult(true);
	}

	/**
	 * @throws UnresolvableBindableException
	 */
	private function mustExist(BindBoundary $bindBoundary): void {
		foreach ($bindBoundary->getBindables() as $bindable) {
			if (!$bindable->doesExist()) {
				throw new UnresolvableBindableException('Bindable does not exist: ' . $bindable->getPath());
			}
		}
	}

	private function else(BindBoundary $bindBoundary): void {
		if (!$this->elseChExistToFalse) {
			return;
		}

		foreach ($bindBoundary->getBindables() as $bindable) {
			$bindable->setExist(false);
		}
	}
}