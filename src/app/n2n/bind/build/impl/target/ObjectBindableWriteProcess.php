<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\plan\Bindable;
use n2n\util\type\ArgUtils;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionUtils;
use n2n\bind\err\BindTargetException;
use n2n\reflection\ObjectCreationFailedException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\TypeName;
use n2n\reflection\property\PropertyValueTypeMismatchException;
use n2n\validation\plan\DetailedName;
use n2n\util\type\TypeConstraints;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\property\PropertyAccessException;

class ObjectBindableWriteProcess {

	/**
	 * @var object[]
	 */
	private array $bindableObjects = [];
	/**
	 * @var PropertiesAnalyzer[]
	 */
	private array $propertiesAnalyzers = [];

	private array $pendingObjWriteCallbacks = [];

	/**
	 * @param Bindable[] $bindables
	 */
	public function __construct(private array $bindables) {
		ArgUtils::valArray($this->bindables, Bindable::class);
	}

	/**
	 * @throws BindTargetException
	 */
	public function process(object $obj): void {
		foreach ($this->bindables as $bindable) {
			if (!$bindable->doesExist()) {
				continue;
			}
			$this->writeValueToObject($bindable->getValue(), $obj, [], $bindable->getName()->toArray(),
					$bindable->getName());
		}

		while (null !== ($callback = array_pop($this->pendingObjWriteCallbacks))) {
			$callback();
		}
	}

	/**
	 * @param mixed $value
	 * @param object $obj
	 * @param array $previousNameParts
	 * @param array $nextNameParts
	 * @param DetailedName $fullDetailedName
	 * @return void
	 * @throws BindTargetException
	 */
	private function writeValueToObject(mixed $value, object $obj, array $previousNameParts, array $nextNameParts,
			DetailedName $fullDetailedName): void {
		$nextNamePart = array_shift($nextNameParts);
		$detailedName = $this->detailedName($nextNamePart, $previousNameParts);

		if (empty($nextNameParts)) {
			$this->writeBindable($value, $obj, $detailedName);
			return;
		}

		$childObj = $this->resolveChildObj($obj, $detailedName, $fullDetailedName);
		$this->writeValueToObject($value, $childObj, $detailedName->toArray(), $nextNameParts, $fullDetailedName);
	}

	/**
	 * @throws BindTargetException
	 */
	private function analyzeProperty(object $obj, DetailedName $detailedName, bool $settingRequired,
			bool $gettingRequired, DetailedName $fullDetailedName = null): AccessProxy {
		$detailedNameStr = $detailedName->__toString();

		try {
			if (!isset($this->propertiesAnalyzers[$detailedNameStr])) {
				$this->propertiesAnalyzers[$detailedNameStr] = new PropertiesAnalyzer(new \ReflectionClass($obj));
			}

			return $this->propertiesAnalyzers[$detailedNameStr]->analyzeProperty($detailedName->getSymbolicName(),
					$settingRequired, $gettingRequired);
		} catch (\ReflectionException $e) {
			throw $this->createCouldNotResolveBindableException($detailedName, $fullDetailedName, $e);
		}
	}

	private function createCouldNotResolveBindableException(DetailedName $detailedName,
			DetailedName $fullDetailedName = null, \Throwable $previous = null, string $reason = null): BindTargetException {
		return new BindTargetException('Can not resolve bindable \'' . ($fullDetailedName ?? $detailedName)
				. ($fullDetailedName === null ? '' : '\', error at \'' . $detailedName) . '\'.'
				. ($reason === null ? '' : ' Reason: ' . $reason), previous: $previous);
	}


	/**
	 * @throws BindTargetException
	 */
	private function writeBindable(mixed $value, object $obj, DetailedName $detailedName): void {
		$accessProxy = $this->analyzeProperty($obj, $detailedName, true, false);

		try {
			$accessProxy->setValue($obj, $value);
		} catch (PropertyAccessException $e) {
			throw new BindTargetException('Could not write bindable: ' .  $detailedName . '\'', 0, $e);
		}
	}

	/**
	 * @param object $obj
	 * @param DetailedName $detailedName
	 * @param DetailedName $fullDetailedName
	 * @return object
	 * @throws BindTargetException
	 */
	private function resolveChildObj(object $obj, DetailedName $detailedName, DetailedName $fullDetailedName): object {
		$detailedNameStr = (string) $detailedName;

		if (isset($this->bindableObjects[$detailedNameStr])) {
			return $this->bindableObjects[$detailedNameStr];
		}

		$accessProxy = $this->analyzeProperty($obj, $detailedName, false, true,
				$fullDetailedName);
		$accessProxy = $accessProxy->createRestricted(TypeConstraints::namedType('object', true));

		try {
			$childObj = $accessProxy->getValue($obj);
		} catch (PropertyAccessException $e) {
			throw $this->createCouldNotResolveBindableException($detailedName, $fullDetailedName, $e);
		}

		if ($childObj === null) {
			$childObj = $this->createObjectFor($accessProxy, $detailedName, $fullDetailedName);

			$this->pendingObjWriteCallbacks[] = function () use ($accessProxy, $obj, $childObj) {
				$accessProxy->setValue($obj, $childObj);
			};
		}

		return $this->bindableObjects[$detailedNameStr] = $childObj;
	}

	/**
	 * @throws BindTargetException
	 */
	private function createObjectFor(AccessProxy $accessProxy, DetailedName $detailedName, DetailedName $fullDetailedName): object {
		if (!$accessProxy->isWritable()) {
			throw $this->createCouldNotResolveBindableException($detailedName, $fullDetailedName,
					reason: 'Property is null and new object could not be created since the property is not writable: '
					. $accessProxy);
		}

		foreach ($accessProxy->getSetterConstraint()->getNamedTypeConstraints() as $namedTypeConstraint) {
			$typeName = $namedTypeConstraint->getTypeName();
			if (!TypeName::isA($typeName, 'object')) {
				continue;
			}

			$class = IllegalStateException::try(fn () => new \ReflectionClass($typeName));
			try {
				return ReflectionUtils::createObject($class);
			} catch (ObjectCreationFailedException $e) {
				throw $this->createCouldNotResolveBindableException($detailedName, $fullDetailedName, $e,
						'Property is null and new object could not be created.');
			}
		}

		throw $this->createCouldNotResolveBindableException($detailedName, $fullDetailedName,
				reason: 'Property is null and new object could not be created since the type could not be determined: '
						. $accessProxy);
	}

	/**
	 * Creates the key used to cache bindable objects
	 * @param string $name
	 * @param array $previousNameParts
	 * @return DetailedName
	 */
	private function detailedName(string $name, array $previousNameParts): DetailedName {
		return new DetailedName([...$previousNameParts, $name]);
	}

//	/**
//	 * @throws BindTargetException
//	 */
//	private function checkWritable(AccessProxy $accessProxy, string $bindableKey) {
//		if (!$accessProxy->isWritable()) {
//			throw new BindTargetException('Property \'' . $bindableKey . '\' is not writable.', 0);
//		}
//	}

}