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

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\mapper\impl\Mappers;
use n2n\validation\build\impl\EmptyMagicContext;
use n2n\util\magic\MagicContext;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\build\impl\Bind;

class CleanStringMapperTest extends TestCase {


	function testAttrs() {
		$this->assertFalse(ctype_print("\x06"));

		$sdm = new DataMap(['firstname' => 'Tester' . "\x06" . 'ich ' , 'lastname' => 'von  ' . "\t" . 'Testen ' . "\r\n"]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['firstname', 'lastname'], Mappers::cleanString())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('Testerich', $tdm->reqString('firstname'));
		$this->assertEquals('von Testen', $tdm->reqString('lastname'));
	}

	function testAttrsValFail() {
		$this->assertFalse(ctype_print("\x06"));

		$sdm = new DataMap([
			'firstname' => str_repeat('A', 256) ,
			'lastname' => 'von  ' . "\t" . 'Testen ' . "\r\n",
			'huii' => [ 'hoi' => null ]
		]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['firstname', 'lastname', 'huii/hoi'],
						Mappers::cleanString(true, 11, 255))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->hasErrors());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('firstname')->getMessages());
		$this->assertCount(1, $errorMap->getChild('lastname')->getMessages());
		$this->assertCount(1, $errorMap->getChild('huii')->getChild('hoi')->getMessages());
	}


}