<?php

namespace n2n\bind\mapper\impl\compose\mock;

use n2n\util\uri\Url;
use DateTime;
use n2n\util\calendar\Date;
use n2n\util\calendar\Time;

class KnownTypesRecord {
	public Url $url;
	public DateTime $dateTime;
	public \DateTimeImmutable $dateTimeImmutable;
	public \DateTimeInterface $dateTimeInterface;
	public Date $date;
	public Time $time;
}