<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\magic\TaskInputMismatchException;

class ValueNotNullClosureMapperTest extends TestCase {
	/**
	 * @throws TaskInputMismatchException
	 */
	function testSkipNull() {
		$dataMap = new DataMap(['clo1' => null, 'clo2' => 'null', 'clo3' => '', 'clo4' => false, 'clo5' => true]);
		$toDataMapValueClosure = new DataMap();
		$toDataMapValueNotNullClosure = new DataMap();

		// valueNotNullClosure return null before execute normal closure, if $value is null
		// valueClosure execute closure regardless of $value
		$closure = (function($value) use ($dataMap) {
			if ($value === null) {
				return 'ERROR';
			}
			if ($value === true) {
				return 'TRUE';
			}
			if ($value === false) {
				return 'FALSE';
			}
			return 'OK';
		});

		//Test with valueNotNullClosure Mapper
		$result = Bind::attrs($dataMap)->toAttrs($toDataMapValueNotNullClosure)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4', 'clo5'], Mappers::valueNotNullClosure($closure))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->isValid());

		$this->assertEquals(null, $toDataMapValueNotNullClosure->reqString('clo1', true)); //default closure not executed, null returned
		$this->assertEquals('OK', $toDataMapValueNotNullClosure->reqString('clo2', true));
		$this->assertEquals('OK', $toDataMapValueNotNullClosure->reqString('clo3', true));
		$this->assertEquals('FALSE', $toDataMapValueNotNullClosure->reqString('clo4', true));
		$this->assertEquals('TRUE', $toDataMapValueNotNullClosure->reqString('clo5', true));

		//same Test with normal valueClosure Mapper
		$result2 = Bind::attrs($dataMap)->toAttrs($toDataMapValueClosure)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4', 'clo5'], Mappers::valueClosure($closure))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result2->isValid());

		$this->assertEquals('ERROR', $toDataMapValueClosure->reqString('clo1', true)); //default closure executed, where we return ERROR if value is null
		$this->assertEquals('OK', $toDataMapValueClosure->reqString('clo2', true));
		$this->assertEquals('OK', $toDataMapValueClosure->reqString('clo3', true));
		$this->assertEquals('FALSE', $toDataMapValueClosure->reqString('clo4', true));
		$this->assertEquals('TRUE', $toDataMapValueClosure->reqString('clo5', true));
	}
}