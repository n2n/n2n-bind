<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\bind\mapper\impl\pipe\RetryValueChanger;

class RetryValueChangersMock {
	public static function simpleNumberSuffixOnRetry(\Closure $closure, int $highestRetryNum = 9999): RetryValueChanger {
		return new SimpleNumberSuffixRetryValueChanger($closure, $highestRetryNum);
	}
}