<?php

namespace n2n\bind\build\impl\compose\prop;

use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindableBoundary;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindPlan;

class SubPropMapper implements Mapper {

	private BindPlan $bindPlan;

	function map(BindableBoundary $bindableBoundary, BindContext $bindContext, MagicContext $magicContext): bool {
		foreach ($bindableBoundary->getBindSources() as $bindSource) {
			if (!$this->bindPlan->exec($bindSource, $bindContext, $magicContext)) {
				return false;
			}
		}

		return true;
	}
}