<?php

namespace n2n\bind\mapper\impl\pipe;

use n2n\util\type\TypeConstraint;

interface RetryValueChanger {
	public int $maxRetryNo {
		get;
		set;
	}

	function getValueTypeConstraint(): TypeConstraint;

	function processValue(mixed $value, ChangeUntilLoopState $state): RetryProcessResult;
}