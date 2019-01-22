<?php
namespace n2n\bind\validation;

interface Validator {
	
	/**
	 * @param ValidatableModel $validatableModel
	 */
	function validate(ValidatableModel $validatableModel);
}

