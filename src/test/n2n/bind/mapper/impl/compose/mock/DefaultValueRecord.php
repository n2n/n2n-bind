<?php

namespace n2n\bind\mapper\impl\compose\mock;

use n2n\util\uri\Url;
use DateTime;
use n2n\util\calendar\Date;
use n2n\util\calendar\Time;

class DefaultValueRecord {
	public ?string $propWithDefault = null;
	public ?string $prop;
}