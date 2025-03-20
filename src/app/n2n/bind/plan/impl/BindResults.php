<?php

namespace n2n\bind\plan\impl;

use n2n\util\ex\IllegalStateException;
use n2n\bind\plan\BindResult;
use n2n\validation\plan\ErrorMap;

class BindResults {

	static function valid(mixed $value = null): BindResult {
		return new class($value) implements BindResult {
			function __construct(private mixed $value) {
			}

			function isValid(): bool {
				return true;
			}

			/**
			 * @deprecated legacy usage only
			 */
			function hasErrors(): bool {
				return false;
			}

			function getErrorMap(): ErrorMap {
				throw new IllegalStateException('BindResult is valid.');
			}

			function get(): mixed {
				return $this->value;
			}
		};
	}

	static function invalid(ErrorMap $errorMap): BindResult {
		return new class($errorMap) implements BindResult {
			function __construct(private ErrorMap $errorMap) {
			}

			function isValid(): bool {
				return false;
			}

			/**
			 * @deprecated legacy usage only
			 */
			function hasErrors(): bool {
				return true;
			}

			function getErrorMap(): ErrorMap {
				return $this->errorMap;
			}

			function get(): mixed {
				throw new IllegalStateException('BindResult is invalid.');
			}
		};
	}
}