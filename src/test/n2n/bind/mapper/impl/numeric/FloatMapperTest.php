<?php

namespace n2n\bind\mapper\impl\numeric;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class FloatMapperTest extends TestCase {
	/**
	 * @throws TaskInputMismatchException
	 */
	function testFloatMapper() {
		$sdm = new DataMap(['valuenull' => null, 'valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuenull', 'valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(mandatory: false, min: 0, max: 100, step: 10))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->isValid());

		$this->assertNull($tdm->reqNumeric('valuenull', true));
		$this->assertEquals(0, $tdm->reqNumeric('valuemin'));
		$this->assertEquals(20, $tdm->reqNumeric('value1'));
		$this->assertEquals(60, $tdm->reqNumeric('value2'));
		$this->assertEquals(100, $tdm->reqNumeric('valuemax'));

	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testFloatMapperFailMin() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['valuemin', 'value1', 'value2', 'valuemax'], Mappers::float(min: 40))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('valuemin')->getMessages()); //min violation
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages()); //min violation
		$this->assertCount(0, $errorMap->getOrCreateChild('value2')->getMessages());
		$this->assertCount(0, $errorMap->getOrCreateChild('valuemax')->getMessages());


	}

	function testFloatMapperFailMax() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(max: 40))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());

		$errorMap = $result->getErrorMap();

		$this->assertCount(0, $errorMap->getOrCreateChild('valuemin')->getMessages());
		$this->assertCount(0, $errorMap->getOrCreateChild('value1')->getMessages());
		$this->assertCount(1, $errorMap->getChild('value2')->getMessages()); //max violation
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages()); //max violation


	}

	function testFloatMapperFailStep() {
		$sdm = new DataMap(['valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(step: 30))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());

		$errorMap = $result->getErrorMap();

		$this->assertCount(0, $errorMap->getOrCreateChild('valuemin')->getMessages());
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages()); //step violation
		$this->assertCount(0, $errorMap->getOrCreateChild('value2')->getMessages());
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages()); //step violation


	}

	function testFloatMapperFailMultipleReasons() {
		$sdm = new DataMap(['valuenull' => null, 'valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['valuenull', 'valuemin', 'value1', 'value2', 'valuemax'],
				Mappers::float(mandatory: true, min: 20, max: 80, step: 30))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());

		$errorMap = $result->getErrorMap();
		// if multiple violations exist only fist is used order: (mandatory > min > max > step)
		// therefore 1 expectedCount regardless of how many rules that are violated
		$this->assertCount(1, $errorMap->getChild('valuenull')->getMessages()); //mandatory violation
		$this->assertEquals('Mandatory', $errorMap->getChild('valuenull')->jsonSerialize()['messages'][0]); //mandatory violation
		$this->assertCount(1, $errorMap->getChild('valuemin')->getMessages()); //min violation
		$this->assertEquals('Min [min = 20]', $errorMap->getChild('valuemin')->jsonSerialize()['messages'][0]); //min violation
		$this->assertCount(1, $errorMap->getChild('value1')->getMessages()); //step violation
		$this->assertEquals('Step [step = 30]', $errorMap->getChild('value1')->jsonSerialize()['messages'][0]); //step violation
		$this->assertCount(0, $errorMap->getOrCreateChild('value2')->getMessages()); //only good value
		$this->assertEquals([], $errorMap->getChild('value2')->jsonSerialize()); //empty Error Map because of good value
		$this->assertCount(1, $errorMap->getChild('valuemax')->getMessages()); //max violation
		$this->assertEquals('Max [max = 80]', $errorMap->getChild('valuemax')->jsonSerialize()['messages'][0]); //max violation


	}

	function testDocsUsage(): void {
		$result = Bind::values('20', null)->map(Mappers::float(min: 0, max: 100, step: 10))->toArray()->exec();
		var_dump($result->get());

		$this->assertEquals([20.0, null], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['valuenull' => null, 'valuemin' => 0, 'value1' => 20, 'value2' => 60, 'valuemax' => 100])
				->props(['valuenull', 'valuemin', 'value1', 'value2', 'valuemax'],
						Mappers::float(mandatory: true, min: 20, max: 80, step: 30))
				->toArray()
				->exec();

		// result will be invalid; each value reports its first violated rule.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('valuenull')->jsonSerialize()['messages'][0]); // "Mandatory"
		var_dump($result->getErrorMap()->getChild('valuemin')->jsonSerialize()['messages'][0]);  // "Min [min = 20]"
		var_dump($result->getErrorMap()->getChild('value1')->jsonSerialize()['messages'][0]);    // "Step [step = 30]"
		var_dump($result->getErrorMap()->getChild('valuemax')->jsonSerialize()['messages'][0]);  // "Max [max = 80]"

		$this->assertFalse($result->isValid());
		$this->assertEquals('Mandatory', $result->getErrorMap()->getChild('valuenull')->jsonSerialize()['messages'][0]);
		$this->assertEquals('Min [min = 20]', $result->getErrorMap()->getChild('valuemin')->jsonSerialize()['messages'][0]);
		$this->assertEquals('Step [step = 30]', $result->getErrorMap()->getChild('value1')->jsonSerialize()['messages'][0]);
		$this->assertEquals('Max [max = 80]', $result->getErrorMap()->getChild('valuemax')->jsonSerialize()['messages'][0]);
	}
}
