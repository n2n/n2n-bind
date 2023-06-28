<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\plan\Bindable;
use n2n\util\type\ArgUtils;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionUtils;
use n2n\bind\err\BindTargetException;
use n2n\reflection\ObjectCreationFailedException;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\reflection\ReflectionException;
use n2n\util\type\TypeUtils;

class ObjectBindableWriteProcess {
	/**
	 * @var object[]
	 */
	private array $bindableObjects = [];
	/**
	 * @var PropertiesAnalyzer[]
	 */
	private array $propertiesAnalyzers = [];

	/**
	 * @param Bindable[] $bindables
	 */
	public function __construct(private array $bindables) {
		ArgUtils::valArray($this->bindables, Bindable::class);
	}

	public function process(object $obj): void {
		foreach ($this->bindables as $bindable) {
			if (!$bindable->doesExist()) {
				continue;
			}
			$this->writeBindableToObject($bindable->getValue(), $obj, $bindable->getName()->toArray());
		}
	}

	/**
	 * @param mixed $value
	 * @param object $obj
	 * @param array $bindableNameParts
	 * @param array $previousNameParts
	 * @return void
	 * @throws BindTargetException
	 * @throws \ReflectionException
	 */
	private function writeBindableToObject(mixed $value, object $obj, array $bindableNameParts, array $previousNameParts = []): void {
		$firstBindableNamePart = array_shift($bindableNameParts);
		$propertyProxy = $this->analyzeProperty($obj, $firstBindableNamePart);
		if ($propertyProxy->getProperty() === null) {
			throw new BindTargetException('Property doesn\'t exist: ' . TypeUtils::prettyMethName(get_class($obj), $firstBindableNamePart));
		}

		$propertyProxy->getProperty()->setAccessible(true);
		if (empty($bindableNameParts)) {
			$this->writeValueToProperty($propertyProxy, $value, $obj);
			return;
		}

		$newBindableObjectClassName = $this->getPropertyTypeClass($propertyProxy);
		$previousNameParts = [...$previousNameParts, $firstBindableNamePart];
		$newBindableObject = $this->getOrCreateBindableObject(implode('/', $previousNameParts), $newBindableObjectClassName);
		$this->writeValueToProperty($propertyProxy, $newBindableObject, $obj);
		$this->writeBindableToObject($value, $newBindableObject, $bindableNameParts, $previousNameParts);
	}

	/**
	 * @param string $path
	 * @param string $className
	 * @return mixed
	 * @throws BindTargetException
	 * @throws \ReflectionException
	 */
	private function getOrCreateBindableObject(string $path, string $className): mixed {
		if (isset($this->bindableObjects[$path] )) {
			return $this->bindableObjects[$path];
		}

		try {
			return $this->bindableObjects[$path] = ReflectionUtils::createObject(new \ReflectionClass($className));
		} catch (ObjectCreationFailedException $e) {
			throw new BindTargetException('Could not create ' . $className . ' to fill ' . $path, 0, $e);
		}
	}

	/**
	 * @throws BindTargetException
	 */
	private function analyzeProperty(object $obj, string $propertyName, array $previousPathParts = []): PropertyAccessProxy {
		try {
			$arrayKey = implode('/', [...$previousPathParts, $propertyName]);

			if (!isset($this->propertiesAnalyzers[$arrayKey])) {
				$this->propertiesAnalyzers[$arrayKey] = new PropertiesAnalyzer(new \ReflectionClass($obj));
			}

			return $this->propertiesAnalyzers[$arrayKey]->analyzeProperty($propertyName);
		} catch (\ReflectionException|ReflectionException $e) {
			throw new BindTargetException('Property \'' . $propertyName . '\' is not accessible.', 0, $e);
		}
	}

	/**
	 * @throws BindTargetException
	 */
	private function writeValueToProperty(PropertyAccessProxy $propertyProxy, mixed $value, object $obj): void {
		try {
			$propertyProxy->setValue($obj, $value);
		} catch (ValueIncompatibleWithConstraintsException|ReflectionException $e) {
			throw new BindTargetException('Could not write: \'' . print_r($value, true) . '\' to ' . $propertyProxy->getProperty()->class
					. '::$' . $propertyProxy->getPropertyName(), 0, $e);
		}
	}

	/**
	 * @todo: what to do with multi type constraints? for now just use first option.
	 * @param PropertyAccessProxy $propertyProxy
	 * @return string|void
	 */
	private function getPropertyTypeClass(PropertyAccessProxy $propertyProxy) {
		$propertyTypeName = $propertyProxy->getProperty()->getType()->getName();
		if ($propertyTypeName !== null) {
			return $propertyTypeName;
		}

		return $propertyProxy->getSetterConstraint()->getNamedTypeConstraints()[0]->getTypeName();
	}
}