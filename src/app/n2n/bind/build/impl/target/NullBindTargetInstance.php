<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\plan\BindTargetInstance;

class NullBindTargetInstance implements BindTargetInstance {

	function write(array $bindables): void {
	}

	function getValue() {
		return null;
	}
}