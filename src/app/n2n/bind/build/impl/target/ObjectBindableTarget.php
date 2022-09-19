<?php
namespace n2n\bind\build\impl\target;

use n2n\bind\plan\BindableTarget;
use n2n\util\type\ArgUtils;
use n2n\bind\plan\Bindable;
use n2n\bind\err\BindTargetException;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\reflection\ReflectionException;

class ObjectBindableTarget implements BindableTarget {
	private object $obj;
	private PropertiesAnalyzer $propertiesAnalyzer;

	function __construct(object $obj) {
		$this->obj = $obj;
		$this->propertiesAnalyzer = new PropertiesAnalyzer(new \ReflectionClass($obj));
	}

	function write(array $bindables): void {
		ArgUtils::valArray($bindables, Bindable::class);

		foreach ($bindables as $bindable) {
			if (!$bindable->doesExist()) {
				continue;
			}

			try {
				$propertyAccessProxy = $this->propertiesAnalyzer->analyzeProperty($bindable->getName());
			} catch (ReflectionException $e) {
				throw new BindTargetException('Property \'' . $bindable->getName() . '\' in '
						. $this->propertiesAnalyzer->getClass()->getName() . ' not accessible.', null, $e);
			}

			try {
				$propertyAccessProxy->setValue($this->obj, $bindable->getValue());
			} catch (ValueIncompatibleWithConstraintsException|ReflectionException $e) {
				throw new BindTargetException('Could not write: \'' . $bindable->getName() . '\'', null, $e);
			}
		}
	}
}