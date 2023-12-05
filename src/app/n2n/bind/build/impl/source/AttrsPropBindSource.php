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
use n2n\validation\plan\DetailedName;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributesException;
use n2n\bind\plan\Bindable;
use n2n\bind\build\impl\compose\prop\PropBindSource;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\plan\BindSource;

class AttrsPropBindSource extends ComposerSourceAdapter implements PropBindSource {

	function __construct(private AttributeReader $attributeReader) {
		parent::__construct([]);
	}

	function acquireRootAsBindable(): Bindable {

	}

	function acquireBindable(string $name): Bindable {
		return $this->getOrCreateBindableByName($name, false);
	}

	public function acquireBindables(string $expression, bool $mustExist): array {
		return [$this->getOrCreateBindableByName($expression, $mustExist)];
	}

	function acquireBindableSource(DetailedName $detailedName, bool $mustExist): ?PropBindSource {
		if ($detailedName->isEmpty()) {
			return $this;
		}

		return new SubPropBindSource();
	}

	/**
	 * @param string $name
	 * @param bool $mustExist
	 * @return Bindable
	 * @throws UnresolvableBindableException
	 */
	private function getOrCreateBindableByName(string $name, bool $mustExist): Bindable {
		$attributePath = AttributePath::create($name);
		$detailedName = new DetailedName($attributePath->toArray());

		return $this->getOrCreateBindable($detailedName, $mustExist);
	}

	function getOrCreateBindable(DetailedName $detailedName, bool $mustExist): Bindable {
		$bindable = $this->getBindable($detailedName);
		if ($bindable !== null && (!$mustExist || $bindable->doesExist())) {
			return $bindable;
		}

		if ($bindable !== null) {
			throw new UnresolvableBindableException('Bindable not found: ' . $attributePath);
		}

		try {
			$value = $this->attributeReader->readAttribute($attributePath);
			$valueBindable = new ValueBindable($detailedName, $value, true);
		} catch (AttributesException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException('Could not resolve bindable: ' . $name, 0, $e);
			}

			$valueBindable = new ValueBindable($detailedName, null, false);
		}

		$this->addBindable($valueBindable);

		return $valueBindable;
	}
}