<?php
namespace n2n\bind\validation;

interface ValidatableModel {

	/**
	 * @return ValidatableProperty[]
	 */
	function getPromotedProperties(): array;
	
	/**
	 * @param string $id
	 * @return ValidatableProperty
	 */
	function getProperty(string $id): ValidatableProperty;
}