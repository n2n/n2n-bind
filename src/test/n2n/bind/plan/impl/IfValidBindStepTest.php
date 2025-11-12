<?php

namespace n2n\bind\plan\impl;

use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\Mapper;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ErrorMap;
use n2n\bind\mapper\MapResult;

class IfValidBindStepTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testPropsIfValid(): void {
		$neverMapper = $this->createMock(Mapper::class);
		$neverMapper->expects($this->never())->method('map');

		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => null, 'prop2' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->ifValid()
				->prop('prop2', $neverMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$magicContext = $this->createMock(MagicContext::class);
		$errorMap = $result->getErrorMap();
		assert($errorMap instanceof ErrorMap);
		$this->assertEqualsIgnoringCase('Mandatory', (string) $errorMap->getChild('prop1')->getMessages()[0]);


		$onceMapper = $this->createMock(Mapper::class);
		$onceMapper->expects($this->once())->method('map')->willReturn(new MapResult(true));

		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => null, 'prop2' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->prop('prop2', $onceMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testValuesIfValid(): void {
		$neverMapper = $this->createMock(Mapper::class);
		$neverMapper->expects($this->never())->method('map');

		$dataMap = new DataMap();
		$result = Bind::values(null, 'valid')->toAttrs($dataMap)
				->map(Mappers::cleanString(true))
				->ifValid()
				->map($neverMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());

		$onceMapper = $this->createMock(Mapper::class);
		$onceMapper->expects($this->once())->method('map')->willReturn(new MapResult(true));

		$dataMap = new DataMap();
		$result = Bind::values(null, 'valid')->toAttrs($dataMap)
				->map(Mappers::cleanString(true))
				->map($onceMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testPropsIfValidContinue(): void {
		$onceMapper = $this->createMock(Mapper::class);
		$onceMapper->expects($this->once())->method('map')->willReturn(new MapResult(true));

		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => 'valid', 'prop2' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->ifValid()
				->prop('prop2', $onceMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
	}

}