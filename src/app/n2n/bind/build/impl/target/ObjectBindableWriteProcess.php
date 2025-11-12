<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
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
use n2n\util\type\attrs\AttributePath;
use n2n\util\type\TypeConstraints;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\property\PropertyAccessException;
use n2n\reflection\property\UninitializedBehaviour;

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
	private AttributePath $contextPath;

	/**
	 * @param Bindable[] $bindables
	 */
	public function __construct(private array $bindables, ?AttributePath $contextPath = null) {
		ArgUtils::valArray($this->bindables, Bindable::class);
		$this->contextPath = $contextPath ?? new AttributePath([]);
	}

	/**
	 * @throws BindTargetException
	 */
	public function process(object $obj): void {
		foreach ($this->bindables as $bindable) {
			if (!$bindable->doesExist() || $bindable->isLogical() || !$bindable->isValid() || $bindable->isDirty()) {
				continue;
			}

			$path = $bindable->getPath()->slice($this->contextPath->size());

			if ($path->isEmpty()) {
				throw new BindTargetException('Root bindable can not be written to object');
			}

			$this->writeValueToObject($bindable->getValue(), $obj, [], $path->toArray(), $path);
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
	 * @param AttributePath $fullAttributePath
	 * @return void
	 * @throws BindTargetException
	 */
	private function writeValueToObject(mixed $value, object $obj, array $previousNameParts, array $nextNameParts,
			AttributePath $fullAttributePath): void {
		$nextNamePart = array_shift($nextNameParts);
		$path = $this->path($nextNamePart, $previousNameParts);

		if (empty($nextNameParts)) {
			$this->writeBindable($value, $obj, $path);
			return;
		}

		$childObj = $this->resolveChildObj($obj, $path, $fullAttributePath);
		$this->writeValueToObject($value, $childObj, $path->toArray(), $nextNameParts, $fullAttributePath);
	}

	/**
	 * @throws BindTargetException
	 */
	private function analyzeProperty(object $obj, AttributePath $path, bool $settingRequired,
			bool $gettingRequired, ?AttributePath $fullAttributePath = null): AccessProxy {
		$pathStr = $path->__toString();

		try {
			if (!isset($this->propertiesAnalyzers[$pathStr])) {
				$this->propertiesAnalyzers[$pathStr] = new PropertiesAnalyzer(new \ReflectionClass($obj),
						superIgnored: false, uninitializedBehaviour: UninitializedBehaviour::RETURN_NULL);
			}

			return $this->propertiesAnalyzers[$pathStr]->analyzeProperty($path->getLast(),
					$settingRequired, $gettingRequired);
		} catch (\ReflectionException $e) {
			throw $this->createCouldNotResolveBindableException($path, $fullAttributePath, $e);
		}
	}

	private function createCouldNotResolveBindableException(AttributePath $path,
			?AttributePath $fullAttributePath = null, ?\Throwable $previous = null, ?string $reason = null): BindTargetException {
		return new BindTargetException('Can not resolve bindable \'' . ($fullAttributePath ?? $path)
				. ($fullAttributePath === null ? '' : '\', error at \'' . $path) . '\'.'
				. ($reason === null ? '' : ' Reason: ' . $reason), previous: $previous);
	}


	/**
	 * @throws BindTargetException
	 */
	private function writeBindable(mixed $value, object $obj, AttributePath $path): void {
		$accessProxy = $this->analyzeProperty($obj, $path, true, false);

		try {
			$accessProxy->setValue($obj, $value);
		} catch (PropertyAccessException $e) {
			throw new BindTargetException('Could not write bindable: ' .  $path . '\'', 0, $e);
		}
	}

	/**
	 * @param object $obj
	 * @param AttributePath $path
	 * @param AttributePath $fullAttributePath
	 * @return object
	 * @throws BindTargetException
	 */
	private function resolveChildObj(object $obj, AttributePath $path, AttributePath $fullAttributePath): object {
		$pathStr = (string) $path;

		if (isset($this->bindableObjects[$pathStr])) {
			return $this->bindableObjects[$pathStr];
		}

		$accessProxy = $this->analyzeProperty($obj, $path, false, true,
				$fullAttributePath);
		$accessProxy = $accessProxy->createRestricted(TypeConstraints::namedType('object', true));

		try {
			$childObj = $accessProxy->getValue($obj);
		} catch (PropertyAccessException $e) {
			throw $this->createCouldNotResolveBindableException($path, $fullAttributePath, $e);
		}

		if ($childObj === null) {
			$childObj = $this->createObjectFor($accessProxy, $path, $fullAttributePath);

			$this->pendingObjWriteCallbacks[] = function () use ($accessProxy, $obj, $childObj) {
				$accessProxy->setValue($obj, $childObj);
			};
		}

		return $this->bindableObjects[$pathStr] = $childObj;
	}

	/**
	 * @throws BindTargetException
	 */
	private function createObjectFor(AccessProxy $accessProxy, AttributePath $path, AttributePath $fullAttributePath): object {
		if (!$accessProxy->isWritable()) {
			throw $this->createCouldNotResolveBindableException($path, $fullAttributePath,
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
				throw $this->createCouldNotResolveBindableException($path, $fullAttributePath, $e,
						'Property is null and new object could not be created.');
			}
		}

		throw $this->createCouldNotResolveBindableException($path, $fullAttributePath,
				reason: 'Property is null and new object could not be created since the type could not be determined: '
						. $accessProxy);
	}

	/**
	 * Creates the key used to cache bindable objects
	 * @param string $name
	 * @param array $previousNameParts
	 * @return AttributePath
	 */
	private function path(string $name, array $previousNameParts): AttributePath {
		return new AttributePath([...$previousNameParts, $name]);
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