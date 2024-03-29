<?php

namespace n2n\bind\build\impl\target;

use n2n\util\type\ArgUtils;
use n2n\bind\plan\Bindable;

class TargetValueCompiler {

	function __construct(private bool $arrayStrict) {
	}

	/**
	 * @param Bindable[] $bindables
	 * @return mixed
	 */
	function compile(array $bindables): mixed {
		ArgUtils::valArray($bindables, Bindable::class);

		$values = [];
		foreach ($bindables as $bindable) {
			if (!$bindable->doesExist() || $bindable->isLogical()) {
				continue;
			}

			$key = (string) $bindable->getPath();
			if (ctype_digit($key)) {
				$key = (int) $key;
			}

			$values[$key] = $bindable->getValue();
		}

		if ($this->arrayStrict || count($values) > 1) {
			return $values;
		}

		if (empty($values)) {
			return null;
		}

		return current($values);
	}
}