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
namespace n2n\bind\build\impl;

use n2n\util\type\attrs\DataMap;
use n2n\util\type\attrs\AttributeReader;
use n2n\bind\build\impl\source\AttrsBindSource;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\build\impl\source\StaticBindSource;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\plan\BindSource;

class Bind {

	static function attrs(AttributeReader|array|BindSource $source): PropBindTo {
		if (is_array($source)) {
			$source = new DataMap($source);
		}

		if ($source instanceof AttributeReader) {
			$source = new AttrsBindSource($source);
		}

		return self::propBindSource($source);
	}

	/**
	 * @param BindSource $source
	 * @return PropBindTo
	 */
	static function propBindSource(BindSource $source): PropBindTo {
		return new PropBindTo($source);
	}

	static function values(...$values): UnionBindTo {
		$bindables = [];
		foreach ($values as $key => $value) {
			$bindables[] = new ValueBindable(new AttributePath([(string) $key]), $value, true);
		}

		return self::unionBindSource(new StaticBindSource($bindables));
	}

	static function unionBindSource(BindSource $source): UnionBindTo {
		return new UnionBindTo($source);
	}

}
