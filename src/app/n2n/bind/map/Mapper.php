<?php 
namespace n2n\bind\map;

use n2n\util\type\TypeConstraint;
use n2n\util\magic\MagicContext;

interface Mapper {
	/**
	 * @return TypeConstraint|NULL
	 */
	function getTypeConstraint(): ?TypeConstraint;
	
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	function marshal($value, MagicContext $magicContext);
	
	/**
	 * @param mixed $value
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	function unmarshal($value, MagicContext $magicContext);
}