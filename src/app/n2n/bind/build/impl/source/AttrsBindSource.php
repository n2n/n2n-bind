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
namespace n2n\bind\build\impl\source;

use n2n\util\type\attrs\AttributeReader;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributesException;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\plan\BindData;
use n2n\util\type\attrs\DataMap;
use n2n\util\type\TypeConstraints;
use n2n\bind\err\BindMismatchException;

class AttrsBindSource extends BindSourceAdapter {

	function __construct(private AttributeReader $attributeReader) {
		parent::__construct([]);
	}

	function acquireBindable(AttributePath $path, bool $mustExist): ?Bindable {
		return $this->getOrCreateBindable($path, $mustExist);
	}

	public function acquireBindables(AttributePath $contextPath, ?string $expression, bool $mustExist): array {
		return [$this->getOrCreateBindable($contextPath->ext($expression), $mustExist)];
	}

//	/**
//	 * @param string $name
//	 * @param bool $mustExist
//	 * @return Bindable
//	 * @throws UnresolvableBindableException
//	 */
//	private function getOrCreateBindableByName(string $name, bool $mustExist): Bindable {
//		$attributePath = AttributePath::create($name);
//		$path = new AttributePath($attributePath->toArray());
//
//		return $this->getOrCreateBindable($path, $mustExist);
//	}

	/**
	 * @throws UnresolvableBindableException
	 */
	function getOrCreateBindable(AttributePath $path, bool $mustExist): Bindable {
		$bindable = $this->getBindable($path);
		if ($bindable !== null && (!$mustExist || $bindable->doesExist())) {
			return $bindable;
		}

		if ($bindable !== null) {
			throw new UnresolvableBindableException('Bindable not found: ' . $path);
		}

		try {
			$value = $this->attributeReader->readAttribute($path);
			$valueBindable = new ValueBindable($path, $value, true);
		} catch (AttributesException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException('Could not resolve bindable: '
						. $path->toAbsoluteString(), 0, $e);
			}

			$valueBindable = new ValueBindable($path, null, false);
		}

		$this->addBindable($valueBindable);

		return $valueBindable;
	}

	/**
	 * @throws UnresolvableBindableException
	 */
	function getRawBindData(AttributePath $path, bool $mustExist): ?BindData {
		try {
			return new BindData(new DataMap($this->attributeReader->readAttribute($path, TypeConstraints::array())));
		} catch (AttributesException $e) {
			if (!$mustExist) {
				return null;
			}

			throw new UnresolvableBindableException('Could not resolve BindData for path: '
					. $path->toAbsoluteString(), previous: $e);
		}
	}
}