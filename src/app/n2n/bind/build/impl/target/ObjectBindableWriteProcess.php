<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\plan\Bindable;
use n2n\util\type\ArgUtils;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionUtils;
use n2n\bind\err\BindTargetException;
use n2n\reflection\ObjectCreationFailedException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\reflection\ReflectionException;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\TypeName;
use n2n\reflection\property\PropertyValueTypeMissmatchException;

class ObjectBindableWriteProcess {
	private const BINDABLE_KEY_SEPARATOR = '/';

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

	/**
	 * @throws \ReflectionException
	 * @throws BindTargetException
	 */
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
		$bindableKey = $this->bindableKey($firstBindableNamePart, $previousNameParts);

		if (empty($bindableNameParts)) {
			$this->writeValueToProperty($value, $obj, $bindableKey);
			return;
		}

		$newBindableObject = $this->getBindableObjectByKey($obj, $bindableKey);
		$this->writeBindableToObject($value, $newBindableObject, $bindableNameParts, [...$previousNameParts, $firstBindableNamePart]);
	}

	/**
	 * @param string $bindableKey
	 * @param string $className
	 * @return mixed
	 * @throws BindTargetException
	 * @throws \ReflectionException
	 */
	private function getOrCreateBindableObject(string $bindableKey, AccessProxy $accessProxy): object {
		if (isset($this->bindableObjects[$bindableKey] )) {
			return $this->bindableObjects[$bindableKey];
		}

		$typeName = $this->getTypeName($accessProxy);
		if ($typeName === null) {
			throw new BindTargetException('No Type found to create  \'' . $bindableKey . ' \'');
		}

		try {
			return $this->bindableObjects[$bindableKey] = ReflectionUtils::createObject(new \ReflectionClass($typeName));
		} catch (ObjectCreationFailedException $e) {
			throw new BindTargetException('Could not create \'' . $typeName . '\' for ' . $bindableKey, 0, $e);
		}
	}

	/**
	 * @throws BindTargetException
	 */
	private function analyzeProperty(object $obj, string $bindableKey): AccessProxy {
		try {
			if (!isset($this->propertiesAnalyzers[$bindableKey])) {
				$this->propertiesAnalyzers[$bindableKey] = new PropertiesAnalyzer(new \ReflectionClass($obj));
			}

			return $this->propertiesAnalyzers[$bindableKey]->analyzeProperty($this->getNameFromBindableKey($bindableKey), false, false);
		} catch (\ReflectionException|ReflectionException $e) {
			throw new BindTargetException('Cant find:  \'' . $bindableKey . '\' on ' . get_class($obj), 0, $e);
		}
	}

	/**
	 * @throws BindTargetException
	 */
	private function writeValueToProperty(mixed $value, object $obj, string $bindableKey): void {
		$accessProxy = $this->analyzeProperty($obj, $bindableKey);
		$this->checkWritable($accessProxy, $bindableKey);

		try {
			$accessProxy->setValue($obj, $value);
		} catch (ValueIncompatibleWithConstraintsException|PropertyValueTypeMissmatchException $e) {
			throw new BindTargetException('Could not write ' . gettype($value) . ' to bindable \''
					. $bindableKey . '\'', 0, $e);
		}
	}

	/**
	 * @param object $obj
	 * @param string $bindableKey
	 * @return object
	 * @throws BindTargetException
	 * @throws \ReflectionException
	 */
	private function getBindableObjectByKey(object $obj, string $bindableKey): object {
		if (isset($this->bindableObjects[$bindableKey])) {
			return $this->bindableObjects[$bindableKey];
		}

		$accessProxy = $this->analyzeProperty($obj, $bindableKey);

		$existingValue = $this->getExistingValueForBindableKey($obj,$accessProxy, $bindableKey);
		if ($existingValue !== null) {
			return $existingValue;
		}

		$this->checkWritable($accessProxy, $bindableKey);

		$propertyTypeReflectionClass = $this->getOrCreateBindableObject($bindableKey, $accessProxy);
		$this->writeValueToProperty($propertyTypeReflectionClass, $obj, $bindableKey);
		return $propertyTypeReflectionClass;
	}

	private function getTypeName(AccessProxy $accessProxy): ?string {
		$typeName = null;
		foreach ($accessProxy->getSetterConstraint()->getNamedTypeConstraints() as $namedTypeConstraint) {
			if (!class_exists($namedTypeConstraint->getTypeName())
					|| !$namedTypeConstraint->isTypeSafe()
					|| TypeName::isA($namedTypeConstraint->getTypeName(), 'object')) {
				continue;
			}
			$typeName = $namedTypeConstraint->getTypeName();
		}
		return $typeName ?? $accessProxy->getProperty()?->getType()?->getName();
	}

	/**
	 * Creates the key used to cache bindable objects
	 * @return string
	 */
	private function bindableKey(string $name, array $previousNameParts): string {
		return implode(self::BINDABLE_KEY_SEPARATOR, [...$previousNameParts, $name]);
	}

	/**
	 * @param string $bindableKey
	 * @return string
	 */
	private function getNameFromBindableKey(string $bindableKey): string {
		$parts = explode(self::BINDABLE_KEY_SEPARATOR, $bindableKey);
		return end($parts);
	}

	/**
	 * @throws BindTargetException
	 */
	private function checkWritable(AccessProxy $accessProxy, string $bindableKey) {
		if (!$accessProxy->isWritable()) {
			throw new BindTargetException('Property \'' . $bindableKey . '\' is not writable.', 0);
		}
	}

	private function getExistingValueForBindableKey(object $obj, AccessProxy $accessProxy, string $bindableKey): ?object {
		try {
			$this->bindableObjects[$bindableKey] = $accessProxy->getValue($obj);
			if ($this->bindableObjects[$bindableKey] !== null) {
				return $this->bindableObjects[$bindableKey];
			}
		} catch (ReflectionException) {

		}

		return null;
	}
}