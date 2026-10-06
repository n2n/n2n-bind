<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\bind\mapper\impl\pipe\RetryValueChanger;

class RetryValueChangers extends \n2n\bind\mapper\impl\pipe\RetryValueChangers {
	public static function numberSuffixOnRetry(\Closure $closure, int $highestRetryNum = 9999): RetryValueChanger {
		return new SimpleNumberSuffixRetryValueChanger($closure, $highestRetryNum);
	}
}