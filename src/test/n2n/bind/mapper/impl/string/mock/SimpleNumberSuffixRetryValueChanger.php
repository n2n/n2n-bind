<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\bind\mapper\impl\pipe\RetryValueChanger;
use n2n\util\type\TypeConstraint;
use n2n\bind\mapper\impl\pipe\ChangeUntilLoopState;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\bind\mapper\impl\pipe\RetryProcessResult;

class SimpleNumberSuffixRetryValueChanger implements RetryValueChanger {

	/**
	 * this changer has only the closure to check if unique, and the maxRetryNo to prevent an overflow / infinite loop
	 */
	public function __construct(
			public \Closure $uniqueValidationClosure,
			public int $maxRetryNo = 999
	) {
	}

	function getValueTypeConstraint(): TypeConstraint {
		return TypeConstraints::string(true, true);
	}


	function processValue(mixed $value, ChangeUntilLoopState $state): RetryProcessResult {
		if ($value === null) {
			return new RetryProcessResult(true);
		}

		$invoker = new MagicMethodInvoker($state->magicContext);
		$invoker->setClosure($this->uniqueValidationClosure);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());
		if ($invoker->invoke(firstArgs: [$value])) {
			return new RetryProcessResult(true);
		}
		$value = $state->tryToGetFirstMappedNonNullValue() . ($state->retryNo + 1);

		return new RetryProcessResult(false, true, $value);
	}
}
