<?php

namespace n2n\bind\mapper\impl\date;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\DateUtils;

class DateTimeSqlMapper extends SingleMapperAdapter {

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::namedType(\DateTimeInterface::class, true));

		if ($value !== null) {
			$bindable->setValue(DateUtils::dateTimeToSql($value));
		}

		return true;
	}

}