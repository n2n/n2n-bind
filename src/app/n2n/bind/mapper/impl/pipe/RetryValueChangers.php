<?php

namespace n2n\bind\mapper\impl\pipe;

class RetryValueChangers {
	public static function uniquePathPart(string|\Stringable|null $fallBack,
			\Closure $closure, int $min, int $max, string $fillStr = 'path', string $numberSuffixOnRetry = '-', int $maxRetryNo = 9999): RetryValueChanger {
		return new PathPartRetryValueChanger($fallBack, $closure, $min, $max, $fillStr, $numberSuffixOnRetry, $maxRetryNo);
	}
}