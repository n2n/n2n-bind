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
namespace n2n\bind\mapper\impl\type;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\util\magic\MagicContext;
use n2n\bind\build\impl\Bind;
use n2n\util\type\TypeConstraints;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\BindMismatchException;

class TypeMapperTest extends TestCase {


	function testAttrs() {
		$sdm = new DataMap(['userIds' => [1, 2, 3]]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['userIds'], Mappers::type(TypeConstraints::array(false, 'int')))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals([1, 2, 3], $tdm->reqArray('userIds'));
	}

	function testAttrsValFail() {
		$this->expectException(BindMismatchException::class);

		$sdm = new DataMap(['userIds' => [1, 2, [3, 4]]]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['userIds'], Mappers::type(TypeConstraints::array(false, 'int')))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}


}