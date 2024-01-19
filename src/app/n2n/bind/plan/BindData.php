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
namespace n2n\bind\plan;

use n2n\util\type\TypeConstraint;

use n2n\util\type\attrs\AttributePath;
use n2n\util\type\attrs\DataMap;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\type\attrs\AttributesException;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\err\BindException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\bind\err\UnresolvableBindableException;

class BindData implements AttributeReader, AttributeWriter {

	/**
	 *
	 * @param DataMap $dataMap
	 */
	public function __construct(private DataMap $dataMap) {
	}

	public function isEmpty(): bool {
		return $this->dataMap->isEmpty();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\util\type\attrs\AttributeReader::containsAttribute()
	 */
	function containsAttribute(AttributePath $path): bool {
		return $this->has($path);
	}
		
	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link BindException} to implement
	 * {@link AttributeReader} correctly.</strong> For safe usage where only {@link StatusException} are desired
	 * use {@link self::req()} instead.
	 * 
	 * {@inheritDoc}
	 * @see \n2n\util\type\attrs\AttributeReader::readAttribute()
	 */
	function readAttribute(AttributePath $path, TypeConstraint $typeConstraint = null, bool $mandatory = true, 
			mixed $defaultValue = null): mixed {
		return $this->dataMap->readAttribute($path, $typeConstraint, $mandatory, $defaultValue);
	}

	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link BindException} to implement
	 * {@link AttributeReader} correclty.</strong>
	 *
	 * {@inheritDoc}
	 */
	function writeAttribute(AttributePath $path, mixed $value): void {
		$this->dataMap->writeAttribute($path, $value);
	}

	/**
	 * <strong>This method throws an {@link AttributesException} instead of a {@link BindException} to implement
	 * {@link AttributeReader} correclty.</strong>
	 *
	 * {@inheritDoc}
	 */
	function removeAttribute(AttributePath $path): bool {
		return $this->dataMap->removeAttribute($path);
	}

	/**
	 * @param string|AttributePath $path
	 * @return boolean
	 */
	function has(mixed $path): bool {
		return $this->dataMap->has($path);
	}

	/**
	 * @param array $paths
	 * @param \Closure $closure
	 * @return BindData
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function mapStrings(array $paths, \Closure $closure): static {
		try {
			$this->dataMap->mapStrings($paths, $closure);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	/**
	 * @param $path
	 * @param \Closure $closure
	 * @return BindData
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function mapString($path, \Closure $closure) {
		try {
			$this->dataMap->mapString($path, $closure);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	/**
	 * @param $path
	 * @param bool $simpleWhitespacesOnly
	 * @return BindData
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function cleanString($path, bool $simpleWhitespacesOnly = true) {
		try {
			$this->dataMap->cleanString($path, $simpleWhitespacesOnly);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}
		return $this;
	}

	/**
	 * @param array $paths
	 * @param bool $simpleWhitespacesOnly
	 * @return BindData
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function cleanStrings(array $paths, bool $simpleWhitespacesOnly = true) {
		try {
			$this->dataMap->cleanStrings($paths, $simpleWhitespacesOnly);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}
		return $this;
	}


	/**
	 * @param $path
	 * @param null $type
	 * @return mixed
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function req($path, $type = null): mixed {
		try {
			return $this->dataMap->req($path, $type);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws BindMismatchException
	 */
	public function opt($path, $type = null, $defaultValue = null) {
		try {
			return $this->dataMap->opt($path, $type, $defaultValue);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function reqScalar($path, bool $nullAllowed = false) {
		return $this->req($path, TypeConstraint::createSimple('scalar', $nullAllowed));
	}


	/**
	 * @throws BindMismatchException
	 */
	public function optScalar($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->opt($path, TypeConstraint::createSimple('scalar', $nullAllowed), $defaultValue);
	}
	
	public function getString($path, bool $mandatory = true, $defaultValue = null, bool $nullAllowed = false) {
		if ($mandatory) {
			return $this->reqString($path, $nullAllowed);
		}
		
		return $this->optString($path, $defaultValue, $nullAllowed);
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function reqString($name, bool $nullAllowed = false, bool $lenient = true) {
		if (!$lenient) {
			return $this->req($name, TypeConstraint::createSimple('string', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqScalar($name, $nullAllowed))) {
			return (string) $value;
		}
		
		return null;
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optString($path, $defaultValue = null, $nullAllowed = true, bool $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('string', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optScalar($path, $defaultValue, $nullAllowed))) {
			return (string) $value;
		}
		
		return null;
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function reqBool($path, bool $nullAllowed = false, $lenient = true) {
		if (!$lenient) {
			return $this->req($path, TypeConstraint::createSimple('bool', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqScalar($path, $nullAllowed))) {
			return (bool) $value;
		}
		
		return null;
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optBool($path, $defaultValue = null, bool $nullAllowed = true, $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('bool', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optScalar($path, $defaultValue, $nullAllowed))) {
			return (bool) $value;
		}
		
		return $defaultValue;
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function reqNumeric($path, bool $nullAllowed = false) {
		return $this->req($path, TypeConstraint::createSimple('numeric', $nullAllowed));
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optNumeric($path, $defaultValue = null, bool $nullAllowed = true) {
		return $this->opt($path, TypeConstraint::createSimple('numeric', $nullAllowed), $defaultValue);
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function reqInt($path, bool $nullAllowed = false, $lenient = true) {
		if (!$lenient) {
			return $this->req($path, TypeConstraint::createSimple('int', $nullAllowed));
		}
		
		if (null !== ($value = $this->reqNumeric($path, $nullAllowed))) {
			return (int) $value;
		}
		
		return null;
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optInt($path, $defaultValue = null, bool $nullAllowed = true, $lenient = true) {
		if (!$lenient) {
			return $this->opt($path, TypeConstraint::createSimple('int', $nullAllowed), $defaultValue);
		}
		
		if (null !== ($value = $this->optNumeric($path, $defaultValue))) {
			return (int) $value;
		}
		
		return null;
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function reqEnum($path, array $allowedValues, bool $nullAllowed = false) {
		try {
			return $this->dataMap->reqEnum($path, $allowedValues, $nullAllowed);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		} catch (MissingAttributeFieldException $e) {
			throw new UnresolvableBindableException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optEnum($path, array $allowedValues, $defaultValue = null, bool $nullAllowed = true) {
		try {
			return $this->dataMap->optEnum($path, $allowedValues, $defaultValue, $nullAllowed);
		} catch (InvalidAttributeException $e) {
			throw new BindMismatchException($e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function reqArray($name, $fieldType = null, bool $nullAllowed = false) {
		return $this->req($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType));
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optArray($name, $fieldType = null, $defaultValue = [], bool $nullAllowed = false) {
		return $this->opt($name, TypeConstraint::createArrayLike('array', $nullAllowed, $fieldType), $defaultValue);
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function reqScalarArray($name, bool $nullAllowed = false, bool $fieldNullAllowed = false): array {
		return $this->reqArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $nullAllowed);
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optScalarArray($name, $defaultValue = [], bool $nullAllowed = false, bool $fieldNullAllowed = false) {
		return $this->optArray($name, TypeConstraint::createSimple('scalar', $fieldNullAllowed), $defaultValue, $nullAllowed);
	}

	/**
	 * @param string|AttributePath|string[] $path
	 * @param bool $nullAllowed
	 * @param int|null $errStatus
	 * @return BindData|null
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function reqBindDataMap($path, bool $nullAllowed = false, int $errStatus = null): ?BindData {
		if (null !== ($array = $this->reqArray($path, null, $nullAllowed))) {
			return new BindData(new DataMap($array));
		}
		
		return null;
	}

	/**
	 * @param string|AttributePath|string[] $path
	 * @param mixed|null $defaultValue
	 * @param bool $nullAllowed
	 * @param int|null $errStatus
	 * @return BindData|null
	 */
	public function optBindDataMap($path, mixed $defaultValue = null, bool $nullAllowed = true, int $errStatus = null): ?BindData {
		if (null !== ($array = $this->optArray($path, null, $defaultValue, $nullAllowed))) {
			return new BindData(new DataMap($array));
		}
		
		return null;
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function reqBindDataMaps($path, bool $nullAllowed = false): array {
		return array_map(fn ($data) => new BindData(new DataMap($data)),
				$this->reqArray($path, 'array', $nullAllowed));
	}

	/**
	 * @throws BindMismatchException
	 */
	public function optBindDataMaps($path, $defaultValue = [], bool $nullAllowed = false) {
		$httpDatas = $this->optArray($path, 'array', null, $nullAllowed);
		if ($httpDatas === null) {
			return $defaultValue;
		}
		
		return array_map(fn ($data) => new BindData(new DataMap($data)), $httpDatas);
	}

	/**
	 * 
	 * @return DataMap
	 */
	function toDataMap(): DataMap {
		return $this->dataMap;
	}
}
