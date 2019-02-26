<?php
namespace n2n\bind\type;

use n2n\bind\Bindable;

class TypeSafeForge {
	private $writable;
	private $models = [];
	
	/**
	 * @param bool $writable
	 */
	function __construct(bool $writable) {
		$this->writable = $writable;
	}
	
	/**
	 * @param \ReflectionClass $class
	 * @throws InvalidBindableExcpetion
	 * @return \n2n\bind\type\TypeSafeModel
	 */
	function optainModel(\ReflectionClass $class) {
		if (isset($this->models[$class->getName()])) {
			return $this->models[$class->getName()];
		}
		
		if (!self::isClassBindable($class)) {
			throw new InvalidBindableExcpetion($class->getName() . ' does not implement ' . Bindable::class);
		}
		
		return $this->models[$class->getName()] = new TypeSafeModel($class, $this->writable);	
	}
		
	/**
	 * @param \ReflectionClass $class
	 * @return boolean
	 */
	static function isClassBindable(\ReflectionClass $class) {
		return $class->implementsInterface(Bindable::class);
	}
}
