<?php

namespace n2n\bind\mapper\impl\date;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\DateUtils;
use DateTimeInterface;
use n2n\util\calendar\Date;

class DateSqlMapper extends SingleMapperAdapter {

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([DateTimeInterface::class, Date::class, 'null']));

		if ($value !== null) {
			$bindable->setValue(DateUtils::dateToSql($value));
		}

		return true;
	}

}