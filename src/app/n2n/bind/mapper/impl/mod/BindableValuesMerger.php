<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\util\type\attrs\AttributePath;
use n2n\util\type\ArgUtils;

class BindableValuesMerger {

	private array $values = [];

	function merge(AttributePath $path, mixed $value): void {
		$this->recursiveMerge($this->values, $path->toArray(), $value);
	}

	private function recursiveMerge(array &$values, array $keys, mixed $value): void {
		ArgUtils::assertTrue(!empty($keys));

		$key = array_shift($keys);

		if (empty($keys)) {
			$values[$key] = $value;
			return;
		}

		if (!isset($values[$key]) || !is_array($values[$key])) {
			$values[$key] = [];
		}

		$this->recursiveMerge($values[$key], $keys, $value);
	}

	function getValues(): array {
		return $this->values;
	}
}