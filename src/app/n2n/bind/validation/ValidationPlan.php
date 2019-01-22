<?php
namespace n2n\bind\validation;

use n2n\l10n\Message;
use n2n\util\type\attrs\Attributes;
use n2n\util\type\attrs\AttributePath;

class ValidationPlan { 
	private $attributes;
	
	function __construct(Attributes $attributes) {
		$this->attributes = $attributes;
	}
	
	function prop($path, Validator ...$validators) {
		
	}
	
	function props(array $paths, Validator ...$validators) {
		foreach (AttributePath::createArray($paths) as $attributePath) {
			$id = (string) $attributePath;
		}
	}
}

class PropertyValidationPlan {
	/**
	 * @var AttributePath
	 */
	private $attributePath;
	
	/**
	 * @param AttributePath $attributePath
	 */
	function __construct(AttributePath $attributePath) {
		$this->attributePath = $attributePath;
	}
	
}

class ValidatableAttributeModel implements ValidatableModel {
	
	
	
	public function getProperty(string $id): ValidatableProperty {
	}

	public function getPromotedProperties(): array {
	}

	
}

class ValidatableAttributeProperty implements ValidatableProperty {
	/**
	 * @var AttributePath
	 */
	private $attributePath;
	
	function __construct(AttributePath $attributePath) {
		$this->attributePath = $attributePath;
	}
	
	public function getValue() {
	}
	public function addError(Message $message) {
	}

	public function isValid(): bool {
	}


}
