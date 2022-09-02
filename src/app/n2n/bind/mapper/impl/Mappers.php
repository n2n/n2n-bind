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
namespace n2n\bind\mapper\impl;

use n2n\bind\mapper\impl\string\CleanStringMapper;
use n2n\bind\mapper\impl\numeric\IntMapper;
use n2n\bind\mapper\impl\string\EmailMapper;

class Mappers {

	static function cleanString(bool $mandatory = false, ?int $minlength = 0, ?int $maxlength = 255): CleanStringMapper {
		return new CleanStringMapper($mandatory, $minlength, $maxlength);
	}

	static function int(bool $mandatory = false, ?int $min = 0, ?int $max = 1000000): IntMapper {
		return new IntMapper($mandatory, $min, $max);
	}

	static function email(bool $mandatory = false) {
		return new EmailMapper($mandatory);
	}
}
