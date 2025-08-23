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

use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindableFactory;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\util\type\TypeUtils;
use n2n\util\type\attrs\AttributeReader;

class StaticBindSource implements BindSource {

	function __construct(private ?array $values = null, private bool $undefinedAsNonExisting = false) {
	}

	function next(mixed $input): BindInstance {
		if ($input === null || $this->values === null) {
			return (new BindInstance(new StaticBindableFactory($this->values ?? []),
					$this->undefinedAsNonExisting))->init();
		}

		if (is_array($input)) {
			return (new BindInstance(new StaticBindableFactory($input), $this->undefinedAsNonExisting))->init();
		}

		throw new IncompatibleBindInputException('AttrsBindSource requires input to be of type array. Given: '
				. TypeUtils::getTypeInfo($input));
	}
}