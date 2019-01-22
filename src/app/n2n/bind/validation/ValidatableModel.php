<?php
namespace n2n\bind\validation;

interface ValidatableModel {

	/**
	 * @return ValidatableProperty[]
	 */
	function getPromotedProperties(): array;
	
	/**
	 * @param string $name
	 * @return ValidatableProperty
	 */
	function getPropertyByName(string $name): ValidatableProperty;
}