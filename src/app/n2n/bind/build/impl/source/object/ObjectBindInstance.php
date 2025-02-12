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

use n2n\bind\build\impl\source\BindInstanceAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\util\type\attrs\AttributePath;
use n2n\reflection\property\PropertyAccessException;
use ArrayAccess;

class ObjectBindInstance extends BindInstanceAdapter {
	/**
	 * @param object $object The source object from which properties are read.
	 */
	public function __construct(private object $object, private ObjectBindAccessProxyCache $proxyCache) {
		parent::__construct();
	}

	/**
	 * @throws ValueNotTraversableException
	 */
	public function createBindable(AttributePath $path, bool $mustExist): Bindable {
		$value = $this->getValueByPath($path, $this->object, $mustExist);
		$valueBindable = new ValueBindable($path, $value, true);
		$this->addBindable($valueBindable);

		return $valueBindable;
	}

	/**
	 * Creates a fresh AttributePathContext from the full path and can provide context in case of Exception.
	 *
	 * @throws ValueNotTraversableException
	 */
	private function getValueByPath(AttributePath $path, object|array $data, bool $mustExist): mixed {
		$pathContext = new ObjectBindTraverseContext($path, $mustExist);
		return $this->getValueByPathContext($pathContext, $data);
	}

	/**
	 * Recursively traverses the data using the provided AttributePathContext.
	 *
	 * @throws ValueNotTraversableException If a segment cannot be found or a nested value is not traversable.
	 */
	private function getValueByPathContext(ObjectBindTraverseContext $pathContext, object|array $data): mixed {
		$segment = $pathContext->shiftSegment();
		if ($segment === null) {
			return $data;
		}

		$value = $this->retrieveValueForSegment($segment, $data, $pathContext);
		if (count($pathContext->getRemainingPath()->toArray()) === 0) {
			return $value;
		}
		if (!$this->isTraversableValue($value)) {
			$this->throwUntraversableTypeException($value, $pathContext);
		}

		return $this->getValueByPathContext($pathContext, $value);
	}

	/**
	 * Retrieves the value corresponding to a segment from the given data.
	 *
	 * @param string $segment The current segment.
	 * @param object|array $data The data to search.
	 * @param ObjectBindTraverseContext $pathContext
	 * @return mixed The value for the given segment.
	 * @throws ValueNotTraversableException
	 */
	private function retrieveValueForSegment(string $segment, object|array $data, ObjectBindTraverseContext $pathContext): mixed {
		if (is_array($data)) {
			if (array_key_exists($segment, $data)) {
				return $data[$segment];
			}

			if (!$pathContext->mustExist()) {
				return null;
			}

			throw new ValueNotTraversableException($this->formatKeyErrorMessage($pathContext, $segment, 'array'));
		} elseif ($data instanceof \ArrayAccess) {
			if ($data->offsetExists($segment)) {
				return $data->offsetGet($segment);
			}

			if (!$pathContext->mustExist()) {
				return null;
			}

			throw new ValueNotTraversableException($this->formatKeyErrorMessage($pathContext, $segment, '\ArrayAccess'));
		} elseif (is_object($data)) {
			$refClass = new \ReflectionClass($data);
			try {
				$valueProxy = $this->proxyCache->getPropertyAccessProxy($refClass, $segment);
				return $valueProxy->getValue($data);
			} catch (\ReflectionException|PropertyAccessException $e) {
				if (!$pathContext->mustExist()) {
					return null;
				}

				throw new ValueNotTraversableException('"' . $pathContext->getTraversedPath() . '"'
						. ' not traversable: ' . $e->getMessage(), null, $e);
			}
		}

		$this->throwUntraversableTypeException($data, $pathContext);
	}

	/**
	 * Checks whether the given value is traversable.
	 *
	 * A value is considered traversable if it is an array, an object, or an instance of ArrayAccess.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private function isTraversableValue(mixed $value): bool {
		return is_array($value) || is_object($value) || ($value instanceof ArrayAccess);
	}

	/**
	 * @throws ValueNotTraversableException
	 */
	private function throwUntraversableTypeException(mixed $untraversableData, ObjectBindTraverseContext $pathContext): void {
		throw new ValueNotTraversableException($pathContext->getTraversedPath()
				. '/ not traversable. Allowed types are: object, array or \ArrayAccess. Given: '
				. gettype($untraversableData) . '.');
	}

	/**
	 * Formats an error message for missing keys in arrays or ArrayAccess objects.
	 */
	private function formatKeyErrorMessage(ObjectBindTraverseContext $pathContext, string $segment, string $type): string {
		$traversedPathParts = explode('/', $pathContext->getTraversedPath());
		array_pop($traversedPathParts);
		$validTraversedPath = implode('/', $traversedPathParts);

		return '"' . $pathContext->getTraversedPath() . '" not traversable: Key "' . $segment . '" does not exist in ' . $type . ' "' . $validTraversedPath . '/"';
	}
}

