<?php
namespace n2n\bind\map\impl;

use n2n\bind\map\Mapper;
use n2n\util\type\TypeConstraint;
use n2n\util\type\TypeConstraints;
use n2n\util\type\ArgUtils;
use n2n\util\DateUtils;

class DateTimeTsMapper implements Mapper {
	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\map\Mapper::unmarshal()
	 */
	public function unmarshal($value) {
		ArgUtils::valType('int', $value);
		
		return DateUtils::createDateTimeFromTimestamp($value);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\map\Mapper::marshal()
	 */
	public function marshal($value) {
		if ($value === null) {
			return $value;
		}
		
		ArgUtils::assertTrue($value instanceof \DateTime);
		return $value->getTimestamp();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\map\Mapper::getTypeConstraint()
	 */
	public function getTypeConstraint(): ?TypeConstraint {
		return TypeConstraints::type(\DateTime::class);
	}
}