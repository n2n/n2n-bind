<?php

namespace n2n\bind\mapper\impl\pipe;

class RetryValueChangers {
	public static function numberSuffixOnRetry(string|\Stringable|null $fallBackValue,
			\Closure $closure, int $min, int $max, string $fillStr = 'path', string $valueNumberSuffixSeparator = ' ',
			int $maxRetryNo = 9999): RetryValueChanger {
		return new NumberSuffixOnRetryValueChanger($fallBackValue, $closure, $min, $max, $fillStr,
				$valueNumberSuffixSeparator, $maxRetryNo);
	}
}