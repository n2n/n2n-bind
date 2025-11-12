<?php

namespace n2n\bind\plan\impl;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindStep;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\BindStepResult;

class IfValidBindStep implements BindStep {

	function __construct() {

	}

	function exec(BindContext $bindContext, MagicContext $magicContext): BindStepResult {
		foreach ($bindContext->unwarpBindInstance()->getBindables() as $bindable) {
			if (!$bindable->isValid()) {
				return new BindStepResult(false);
			}
		}

		return new BindStepResult(true);
	}
}