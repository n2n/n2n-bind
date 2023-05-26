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
		$fdm = new DataMap(['value' => 20, 'mandatory' => true, 'min' => 10, 'max' => 100, 'step'=> 10]);
		$tdm = new DataMap();

		$result = Bind::attrs($fdm)->toAttrs($tdm)->props(['mandatory', 'min', 'max', 'step'], Mappers::float())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('10', $tdm->reqString('min'));
		$this->assertEquals('test@testerich.ch', $tdm->reqString('email2'));
		$this->assertEquals('test@testerich.ch', $tdm->reqString('email3'));
	}
}
