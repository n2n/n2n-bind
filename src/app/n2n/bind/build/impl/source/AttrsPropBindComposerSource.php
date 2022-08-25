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
use n2n\bind\build\impl\compose\prop\PropBindComposerSource;
use n2n\bind\plan\impl\ValueBindable;

class AttrsPropBindComposerSource extends ComposerSourceAdapter implements PropBindComposerSource {

	function __construct(private AttributeReader $attributeReader) {
		parent::__construct([]);
	}

	function acquireBindable(string $name): Bindable {
		return $this->getOrCreateBindable($name, false);
	}

	public function acquireBindables(string $expression, bool $mustExist): array {
		return [$this->getOrCreateBindable($expression, $mustExist)];
	}

	/**
	 * @param string $name
	 * @param bool $mustExist
	 * @return Bindable
	 */
	private function getOrCreateBindable(string $name, bool $mustExist) {
		$attributePath = AttributePath::create($name);
		$detailedName = new DetailedName($attributePath->toArray());

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
				throw new UnresolvableBindableException('Could not resolve bindable: ' . $name, null, $e);
			}

			$valueBindable = new ValueBindable($detailedName, null, false);
		}

		$this->addBindable($valueBindable);

		return $valueBindable;
	}
}