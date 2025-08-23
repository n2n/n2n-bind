<?php

namespace n2n\bind\mapper\impl\compose\mock;

use n2n\util\type\custom\Undefined;


class SimpleBaseRecord {

	public string $prop;
	public string|null $nullableProp;
	public string|null|Undefined $undefNullableProp;
	public mixed $mixedProp;
}