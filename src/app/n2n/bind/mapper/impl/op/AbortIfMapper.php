<?php

namespace n2n\bind\mapper\impl\op;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\MapResult;

class AbortIfMapper implements Mapper  {

	function __construct(private AbortIfCondition $abortIfCondition) {

	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		foreach ($bindBoundary->getBindables() as $bindable) {
			if (!$bindable->doesExist()) {
				continue;
			}

			if ($this->abortIfCondition === AbortIfCondition::INVALID && !$bindable->isValid()) {
				return new MapResult(false);
			}

			if ($this->abortIfCondition === AbortIfCondition::DIRTY && $bindable->isDirty()) {
				return new MapResult(false);
			}
		}

		return new MapResult(true);
	}
}

