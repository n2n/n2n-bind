<?php

namespace n2n\bind\mapper\impl\pipe;

class RetryValueChangers {

	/**
	 * @param \Closure $closure (string $value): bool — the validity/uniqueness test
	 * @param int $min minimum length (value is padded with $fillStr)
	 * @param int $max maximum length (value is cropped)
	 * @param string|\Stringable|null $fallBackOnNullValue used when the input is null
	 * @param string $fillStr
	 * @param string $valueNumberSuffixSeparator inserted before the retry number
	 * @param int $maxRetryNo
	 * @return RetryValueChanger
	 */
	public static function numberSuffixOnRetry(?\Closure $closure, int $min, int $max,
			string|\Stringable|null $fallBackOnNullValue,
			string $fillStr = 'unnamed', string $valueNumberSuffixSeparator = ' ',
			int $maxRetryNo = 9999): RetryValueChanger {
		return new NumberSuffixOnRetryValueChanger($closure, $min, $max, $fallBackOnNullValue, $fillStr,
				$valueNumberSuffixSeparator, $maxRetryNo);
	}
}