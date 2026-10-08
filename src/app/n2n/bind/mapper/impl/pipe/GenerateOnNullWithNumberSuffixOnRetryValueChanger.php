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

class GenerateOnNullWithNumberSuffixOnRetryValueChanger implements RetryValueChanger {

	private \WeakMap $retryStateInfoMap;

	public function __construct(
			public ?\Closure $validationClosure,
			public int $minLength,
			public int $maxLength,
			public string $fallBackOnNullValue,
			public string $fillStr,
			public string $valueNumberSuffixSeparator,
			public int $maxRetryNo = 9999) {

		ArgUtils::assertTrue(ValidationUtils::isNotShorterThan($fillStr, 1),
				'Invalid fill str, make sure it is at least 1 char long: ' . $fillStr);

		ArgUtils::assertTrue(!($this->minLength > $this->maxLength),
				'Maxlength need to be greater or equal to minlength.');

		ArgUtils::assertTrue(($this->maxLength > (mb_strlen($this->valueNumberSuffixSeparator) + mb_strlen($this->maxRetryNo))),
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
		if ($state->retryNo === 0 && $value !== null) {
			return new RetryProcessResult(true);
		}

		$retryStateInfo = $this->getWeakMapInfo($state);
		if ($state->retryNo === 0 && $value !== null) {
			if (!$retryStateInfo->fallbackApplied) {
				$retryStateInfo->fallbackApplied = true;
				$state->retryNo = -1;
				return new RetryProcessResult(false, true, $this->fallBackOnNullValue);
			}

			if (!$retryStateInfo->fillStrApplied) {
				$retryStateInfo->fillStrApplied = true;
				$state->retryNo = -1;
				return new RetryProcessResult(false, true, $this->fillToMinlength($value));
			}


//			return new RetryProcessResult(false);
		}

		if ($value === null) {
			throw new MisconfiguredMapperException(self::class
					. ' was not able to adjust value. Possible illegal fillStr: ' . $this->fillStr);
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

		if ($this->validationClosure === null) {
			return new RetryProcessResult(true);
		}

		$invoker = new MagicMethodInvoker($state->magicContext);
		$invoker->setClosure($this->validationClosure);
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
							($this->maxLength - (mb_strlen($this->valueNumberSuffixSeparator) + strlen((string) $no))))
					. $this->valueNumberSuffixSeparator
					. $no;
		}

		$modified = $manipulatedValue !== $value;
		return $manipulatedValue;
	}

	private function fillToMinlength(?string $value, bool &$modified = false): string {
		$manipulatedValue = ($value === null ? $this->fillStr : $value);
		while (mb_strlen($manipulatedValue) < $this->minLength) {
			$manipulatedValue .= $this->valueNumberSuffixSeparator . $this->fillStr;
		}
		$modified = $manipulatedValue !== $value;
		return $manipulatedValue;
	}

}


