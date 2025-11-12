<?php

namespace n2n\bind\plan\impl;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindStep;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\BindQueue;
use n2n\bind\plan\BindStepResult;

class WriteBindStep implements BindStep {

	function __construct() {

	}

	function exec(BindContext $bindContext, MagicContext $magicContext): BindStepResult {
		$bindContext->unwrapBindTargetInstance()?->write($bindContext->unwarpBindInstance()->getBindables());
		return new BindStepResult(true, targetWritten: true);
	}
}