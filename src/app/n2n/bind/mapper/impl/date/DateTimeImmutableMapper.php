<?php
namespace n2n\bind\mapper\impl\date;

use DateTimeInterface;
use DateTime;

class DateTimeImmutableMapper extends DateTimeInterfaceMapperAdapter {
	function __construct(bool $mandatory, ?DateTimeInterface $min = null, ?DateTimeInterface $max = null) {
		parent::__construct($mandatory, $min, $max);
	}

	protected function createValueFromDateTimeInterface(DateTimeInterface $value): DateTime {
		return DateTime::createFromInterface($value);
	}
}