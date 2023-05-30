<?php

namespace n2n\bind\mapper\impl\numeric;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\magic\MagicTaskExecutionException;

class FloatMapperTest extends TestCase {
	/**
	 * @throws MagicTaskExecutionException
	 */
	function testFloatMapper() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(mandatory: false, min: 0, max: 100, step: 10))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->hasErrors());

		$this->assertEquals('0', $tdm->reqString('valuemin'));
		$this->assertEquals('20', $tdm->reqString('value1'));
		$this->assertEquals('60', $tdm->reqString('value2'));
		$this->assertEquals('100', $tdm->reqString('valuemax'));

	}

	function testFloatMapperFailMin() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(min: 40))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('valuemin')->getMessages());
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages());
		$this->assertCount(0, $errorMap->getChild('value2')->getMessages());
		$this->assertCount(0, $errorMap->getChild('valuemax')->getMessages());


	}

	function testFloatMapperFailMax() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(max: 40))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());

		$errorMap = $result->getErrorMap();

		$this->assertCount(0, $errorMap->getChild('valuemin')->getMessages());
		$this->assertCount(0, $errorMap->getChild('value1')->getMessages());
		$this->assertCount(1, $errorMap->getChild('value2')->getMessages());
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages());


	}

	function testFloatMapperFailStep() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(step: 30))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());

		$errorMap = $result->getErrorMap();

		$this->assertCount(0, $errorMap->getChild('valuemin')->getMessages());
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages());
		$this->assertCount(0, $errorMap->getChild('value2')->getMessages());
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages());


	}

	function testFloatMapperFailMultipleReasons() {
		$sdm = new DataMap(['valuenull' => null, 'valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuenull', 'valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(mandatory: true, min: 20, max: 80, step: 30))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());

		$errorMap = $result->getErrorMap();

		//if multiple violations exist only fist is used order: (mandatory > min > max > step)
		$this->assertCount(1, $errorMap->getChild('valuenull')->getMessages()); //mandatory violation
		$this->assertCount(1, $errorMap->getChild('valuemin')->getMessages()); //min violation
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages()); //step violation
		$this->assertCount(0, $errorMap->getChild('value2')->getMessages()); //only good value
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages()); //max violation


	}
}
