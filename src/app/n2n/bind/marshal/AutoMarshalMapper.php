<?php
namespace n2n\bind\marshal;

use n2n\util\type\TypeConstraint;
use n2n\util\ex\UnsupportedOperationException;
use n2n\bind\mapper\Mapper;
use n2n\util\magic\MagicContext;

class AutoMarshalMapper implements Mapper {
	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\mapper\Mapper::unmarshal()
	 */
	public function unmarshal($value, MagicContext $magicContext) {
		throw new UnsupportedOperationException();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\mapper\Mapper::marshal()
	 */
	public function marshal($value, MagicContext $magicContext) {
		if ($value === null || is_scalar($value)) {
			return $value;
		}
		
		if ($value instanceof \DateTime) {
			return $value->getTimestamp();
		}
		
		throw new \InvalidArgumentException();
	}
	
	public function getTypeConstraint(): ?TypeConstraint {
		return null;
	}
	
	/**
	 * @param TypeConstraint $typeConstraint
	 * @return boolean
	 */
	public static function testCompatibility(TypeConstraint $typeConstraint) {
		return $typeConstraint->isTypeSafe()
				&& ($typeConstraint->isScalar() || $typeConstraint->getTypeName() == \DateTime::class);
	}
}