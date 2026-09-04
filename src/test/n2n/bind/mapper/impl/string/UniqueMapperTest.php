<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\bind\mapper\impl\valobj\ValueObjectMock;
use n2n\bind\mapper\impl\enum\mock\MockEnum;

class UniqueMapperTest extends TestCase {

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 */
	function testAttrs() {
		$dm = new DataMap(['uniquePart1' => null, 'uniquePart2' => new ValueObjectMock('asdf@appagic.test'),
				'uniquePart3' => ' ', 'uniquePart4' => MockEnum::EUROPE_ZURICH->value]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['uniquePart1', 'uniquePart2', 'uniquePart3', 'uniquePart3', 'uniquePart4'],
						Mappers::unique((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'somepath']);
						})))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals(null, $tdm->reqString('uniquePart1', true));
		$this->assertEquals('asdf@appagic.test', $tdm->reqString('uniquePart2'));
		$this->assertEquals(' ', $tdm->reqString('uniquePart3'));
		$this->assertEquals(MockEnum::EUROPE_ZURICH->value, $tdm->reqString('uniquePart4'));
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testAttrsValFail() {
		$dm = new DataMap(['uniquePart1' => null, 'uniquePart2' => new ValueObjectMock('asdf@appagic.test'),
				'uniquePart3' => '§§§§', 'uniquePart4' => 'blubb']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['uniquePart1', 'uniquePart2', 'uniquePart3', 'uniquePart4'],
						Mappers::unique((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'somepath', new ValueObjectMock('asdf@appagic.test')]);
						})))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertNull($errorMap->getChild('uniquePart1')); //is empty
		$this->assertCount(1, $errorMap->getChild('uniquePart2')->getMessages()); //already used, unique fails
		$this->assertEquals('Already Taken', $errorMap->getChild('uniquePart2')->jsonSerialize()['messages'][0]); //unique violation
		$this->assertNull($errorMap->getChild('uniquePart3')); //is empty

		$this->assertCount(1, $errorMap->getChild('uniquePart4')->getMessages()); //already used, unique fails
		$this->assertEquals('Already Taken', $errorMap->getChild('uniquePart4')->jsonSerialize()['messages'][0]); //unique violation
	}


	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFailCustomErrorMessages() {
		$dm = new DataMap(['uniquePart1' => 'blubb']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['uniquePart1'],
						Mappers::unique((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'somepath']);
						}))->setUniqueErrorMessage('CustomErrorMessage unique'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('uniquePart1')->getMessages()); //already used, unique fails
		$this->assertEquals('CustomErrorMessage unique', $errorMap->getChild('uniquePart1')->jsonSerialize()['messages'][0]); //unique violation
	}

}