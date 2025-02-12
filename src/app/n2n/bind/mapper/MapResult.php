<?php

namespace n2n\bind\mapper;

use n2n\util\type\attrs\InvalidAttributeException;

class MapResult {

	/**
	 * @param bool $ok false if a Mapper could not perform a modification of value due to errors of the passed bindables.
	 *     The bind process will be aborted in this case
	 * @param bool|null $skipNext if true next Mapper of the current BindGroup must be skipped.
	 */
	function __construct(private bool $ok = true, private bool $skipNext = false) {
	}

	function isOk(): bool {
		return $this->ok;
	}

	function isSkipNext(): bool {
		return $this->skipNext === true;
	}

	function merge(MapResult $mapResult): MapResult {
		return new MapResult($mapResult->ok && $this->ok,
				$mapResult->skipNext || $this->skipNext);
	}

	static function fromArg(mixed $arg): MapResult {
		if ($arg === null) {
			return new MapResult(true);
		}

		if (is_bool($arg)) {
			return new MapResult($arg);
		}

		if ($arg instanceof MapResult) {
			return $arg;
		}

		throw new \InvalidArgumentException('Could not convert passed value to ' . MapResult::class);
	}

}