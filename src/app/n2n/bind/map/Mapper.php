<?php 
namespace n2n\bind\map;

use n2n\util\type\TypeConstraint;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationContext;
use n2n\bind\plan\BindableTarget;
use n2n\bind\plan\Bindable;

interface Mapper  {
	/**
	 * @return TypeConstraint|NULL
	 */
	function getTypeConstraint(): ?TypeConstraint;

	/**
	 * @param Bindable[] $bindables
	 * @param ValidationContext $validationContext
	 * @param MagicContext $magicContext
	 * @return array
	 */
	function map(array $bindables, ValidationContext $validationContext, MagicContext $magicContext): array;

}