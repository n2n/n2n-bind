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
namespace n2n\bind\build\impl\source\object;

use n2n\bind\build\impl\source\BindableFactoryAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\util\type\attrs\AttributePath;
use n2n\reflection\property\PropertyAccessException;
use n2n\reflection\property\UnknownPropertyException;
use n2n\util\type\TypeUtils;
use n2n\util\ex\ExUtils;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\reflection\property\UninitializedBehaviour;

class ObjectBindableFactory extends BindableFactoryAdapter {
	/**
	 * @param object $object The source object from which properties are read.
	 */
	public function __construct(private object $object, private ObjectBindAccessProxyCache $proxyCache) {
	}

	public function createBindable(AttributePath $path, bool $mustExist): Bindable {
		$pathContext = new ObjectBindTraverseState($path, $mustExist);
		try {
			$retrievedValue = $this->resolveValue($pathContext, $this->object);
		} catch (ValueNotTraversableException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException($e->getMessage(), previous: $e);
			}

			throw new BindMismatchException($e->getMessage(), previous: $e);
		}

		return new ValueBindable($path, $retrievedValue->value, $retrievedValue->exists);
	}

	/**
	 * Recursively traverses the data using the provided AttributePathContext.
	 *
	 * @throws ValueNotTraversableException If a segment cannot be found or a nested value is not traversable.
	 */
	private function resolveValue(ObjectBindTraverseState $pathState, mixed $value): RetrievedValue {
		$segment = $pathState->shiftSegment();
		if ($segment === null) {
			return new RetrievedValue($value, true);
		}

		$retrievedValue = $this->retrieveValueForSegment($segment, $value, $pathState);
		if (!$retrievedValue->exists) {
			return $retrievedValue;
		}

		return $this->resolveValue($pathState, $retrievedValue->value);
	}

	/**
	 * Retrieves the value corresponding to a segment from the given data.
	 *
	 * @param string $segment The current segment.
	 * @param mixed $value The data to search.
	 * @param ObjectBindTraverseState $pathContext
	 * @return mixed The value for the given segment.
	 * @throws ValueNotTraversableException
	 */
	private function retrieveValueForSegment(string $segment, mixed $value, ObjectBindTraverseState $pathContext): RetrievedValue {
		if (is_array($value)) {
			if (array_key_exists($segment, $value)) {
				return new RetrievedValue($value[$segment], true);
			}

			if (!$pathContext->mustExist()) {
				return new RetrievedValue(null, false);
			}

			throw new ValueNotTraversableException($this->formatKeyErrorMessage($pathContext, $segment, 'array'));
		}

		if ($value instanceof \ArrayAccess) {
			if ($value->offsetExists($segment)) {
				return new RetrievedValue($value->offsetGet($segment), true);
			}

			if (!$pathContext->mustExist()) {
				return new RetrievedValue(null, false);
			}

			throw new ValueNotTraversableException($this->formatKeyErrorMessage($pathContext, $segment,
					get_class($value)));
		}

		if (is_object($value)) {
			$refClass = ExUtils::try(fn () => new \ReflectionClass($value));
			try {
				$valueProxy = $this->proxyCache->getPropertyAccessProxy($refClass, $segment);
				return new RetrievedValue($valueProxy->getValue($value), true);
			} catch (UnknownPropertyException $e) {
				if (!$pathContext->mustExist()) {
					return new RetrievedValue(null, false);
				}
				throw new ValueNotTraversableException('Can not resolve path "' . $pathContext->getTraversedPath()
						. '". Path "' . $pathContext->getTraversedPath()->slice(0, -1) . '" resolved a value of type '
						. TypeUtils::getTypeInfo($value) . ' which does not contain a property "' . $segment . '".');
			} catch (PropertyAccessException|\ReflectionException $e) {
				throw new ValueNotTraversableException('Can not resolve path "'
						. $pathContext->getTraversedPath() . '". Reason: ' . $e->getMessage(), previous: $e);
			}
		}

		throw new ValueNotTraversableException('Can not resolve path "' . $pathContext->getTraversedPath()
				. '". Path "' . $pathContext->getTraversedPath()->slice(0, -1) . '" resolved a value of type '
				. TypeUtils::getTypeInfo($value)
				. ' which is not traversable. Traversable types are: object, array or \ArrayAccess.');
	}

	/**
	 * Formats an error message for missing keys in arrays or ArrayAccess objects.
	 */
	private function formatKeyErrorMessage(ObjectBindTraverseState $pathContext, string $segment, string $type): string {
		return 'Can not resolve path "' . $pathContext->getTraversedPath() . '". Key "' . $segment . '" does not exist in '
				. $type . ' resolved by path "' . $pathContext->getTraversedPath()->slice(0, -1) . '"';
	}
}

class RetrievedValue {

	function __construct(public readonly mixed $value, public readonly bool $exists) {
	}
}