<?php

namespace n2n\bind\mapper\impl\pipe;

use n2n\util\type\TypeConstraint;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;
use Stringable;
use n2n\util\type\ArgUtils;
use n2n\validation\validator\impl\ValidationUtils;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\bind\err\MisconfiguredMapperException;

class PathPartRetryValueChanger implements RetryValueChanger {

	private \WeakMap $retryStateInfoMap;

	public function __construct(
			public string|Stringable|null $fallBackValue,
			public \Closure $uniqueValidationClosure,
			public int $minLength,
			public int $maxLength,
			public string $fillStr,
			public string $numberSuffixOnRetry,
			public int $maxRetryNo = 9999) {

		ArgUtils::assertTrue(ValidationUtils::isNotShorterThan($fillStr, 1),
				'Invalid fill str, make sure it is at least 1 char long: ' . $fillStr);

		ArgUtils::assertTrue(!($this->minLength > $this->maxLength),
				'Maxlength need to be greater or equal to minlength.');

		ArgUtils::assertTrue(($this->maxLength > (mb_strlen($this->numberSuffixOnRetry) + mb_strlen($this->maxRetryNo))),
				'maxLength need to be greater than (numberSuffixOnRetry + maxRetries) length');

		$this->retryStateInfoMap = new \WeakMap();
	}

	private function getWeakMapInfo(ChangeUntilLoopState $state): RetryStateInfo {
		if (!$this->retryStateInfoMap->offsetExists($state)) {
			$this->retryStateInfoMap->offsetSet($state, new RetryStateInfo());
		}

		return $this->retryStateInfoMap->offsetGet($state);

	}

	function getValueTypeConstraint(): TypeConstraint {
		return TypeConstraints::string(true, true);
	}

	final function processValue(mixed $value, ChangeUntilLoopState $state): RetryProcessResult {
		$retryStateInfo = $this->getWeakMapInfo($state);
		if ($value === null && $state->retryNo === 0) {
			if (!$retryStateInfo->fallbackApplied) {
				$retryStateInfo->fallbackApplied = true;
				if ($this->fallBackValue !== null) {
					$state->retryNo = -1;
					return new RetryProcessResult(false, true, $this->fallBackValue);
				}
			}

			if (!$retryStateInfo->fillStrApplied) {
				$retryStateInfo->fillStrApplied = true;
				if ($state->originalValue !== null) {
					$state->retryNo = -1;
					return new RetryProcessResult(false, true, $this->fillToMinlength($value));
				}
			}

			if ($state->originalValue !== null) {
				throw new MisconfiguredMapperException(self::class
						. ' was not able to adjust value. Possible illegal fillStr: ' . $this->fillStr);
			}

			return new RetryProcessResult(true);
		}


		$fillModified = false;
		$reducedModified = false;
		$value = $this->fillToMinlength($value, $fillModified);
		$value = $this->reduceStringLength($value, null, $reducedModified);
		if ($fillModified || $reducedModified) {
			if ($retryStateInfo->modifiedAtLeastOnce) {
				throw new MisconfiguredMapperException(self::class
						. ' was not able to adjust value. Possible illegal fillStr: ' . $this->fillStr);
			}

			$retryStateInfo->modifiedAtLeastOnce = true;
			return new RetryProcessResult(false, true, $value);
		}

		$retryStateInfo->validatedAtLeastOnce = true;

		$invoker = new MagicMethodInvoker($state->magicContext);
		$invoker->setClosure($this->uniqueValidationClosure);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());
		if ($invoker->invoke(firstArgs: [$value])) {
			return new RetryProcessResult(true);
		}

		$manipulatedValue = $state->tryToGetFirstMappedNonNullValue();
		$manipulatedValue = $this->fillToMinlength($manipulatedValue);
		$state->retryNo = max($state->retryNo, 1);
		$manipulatedValue = $this->reduceStringLength($manipulatedValue, $state->retryNo + 1);

		return new RetryProcessResult(false, true, $manipulatedValue);
	}

	function reduceStringLength(string $value, ?int $no, bool &$modified = false): string {
		if ($no === null) {
			$manipulatedValue =  StringUtils::reduce($value, $this->maxLength);
		} else {
			$manipulatedValue = StringUtils::reduce(
							$value,
							($this->maxLength - (mb_strlen($this->numberSuffixOnRetry) + strlen((string) $no))))
					. $this->numberSuffixOnRetry
					. $no;
		}

		$modified = $manipulatedValue !== $value;
		return $manipulatedValue;
	}

	private function fillToMinlength(?string $value, bool &$modified = false): string {
		$manipulatedValue = ($value === null ? $this->fillStr : $value);
		while (mb_strlen($manipulatedValue) < $this->minLength) {
			$manipulatedValue .= $this->numberSuffixOnRetry . $this->fillStr;
		}
		$modified = $manipulatedValue !== $value;
		return $manipulatedValue;
	}

}


class RetryStateInfo {
	public bool $fallbackApplied = false;
	public bool $fillStrApplied = false;

	public bool $modifiedAtLeastOnce = false;
	public bool $validatedAtLeastOnce = false;
}