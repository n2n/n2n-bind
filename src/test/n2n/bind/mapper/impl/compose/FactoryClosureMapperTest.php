<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindContext;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindTargetException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\bind\err\BindMismatchException;

class FactoryClosureMapperTest extends TestCase {


	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testFactoryCalled(): void {
		$dataMap = new DataMap(['minNum' => 2, 'maxNum' => 4]);
		$targetDataMap = new DataMap();

		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->optProp('maxNum', Mappers::factoryClosure(function (BindContext $bindContext) {
					$minNum = $bindContext->getValue('minNum');
					$this->assertEquals(2, $minNum);

					return Mappers::valueClosure(function (int $maxNum) {
						$this->assertEquals(4, $maxNum);
						return $maxNum + 1;
					});
				}))
				->optProp('minNum', Mappers::int())
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(2, $targetDataMap->req('minNum'));
		$this->assertEquals(5, $targetDataMap->req('maxNum'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testFactoryReturnsNull(): void {
		$dataMap = new DataMap(['minNum' => 2, 'maxNum' => 4]);
		$targetDataMap = new DataMap();

		$called = false;
		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->optProp('maxNum', Mappers::factoryClosure(function (BindContext $bindContext) use (&$called) {
					$called = true;
					return null;
				}))
				->optProp('minNum', Mappers::int())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($called);
		$this->assertEquals(2, $targetDataMap->req('minNum'));
		$this->assertEquals(4, $targetDataMap->req('maxNum'));
	}

	/**
	 * @throws \Throwable
	 */
	function testMinMaxNoMinExpectErrorMapMaxToLow(): void {
		$dataMap = new DataMap(['minNum' => 6, 'maxNum' => 4]);
		$targetDataMap = new DataMap();

		$result = Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->optProp('maxNum', Mappers::factoryClosure(function (BindContext $bindContext) {
					return Mappers::int(false, $bindContext->getValue('minNum', false) ?? 0);
				}))
				->optProp('minNum', Mappers::int())
				->exec($this->createMock(MagicContext::class));
		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('maxNum')->getMessages());
		$this->assertEquals('Min [min = 6]', $errorMap->getChild('maxNum')->jsonSerialize()['messages'][0]);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMinMaxExtMin(): void {
		$dataMap = new DataMap(['maxNum' => 4]);
		$targetDataMap = new DataMap();
		$min = 6;
		$result = Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->optProp('maxNum', Mappers::factoryClosure(function (BindContext $bindContext) use ($min) {
					return Mappers::int(false, $bindContext->getValue('minNum', false) ?? $min ?? 0);
				}))
				->optProp('minNum', Mappers::int())
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('maxNum')->getMessages());
		$this->assertEquals('Min [min = 6]', $errorMap->getChild('maxNum')->jsonSerialize()['messages'][0]);

	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testMinMaxAutoUpMax(): void {
		$dataMap = new DataMap(['minNum' => 6, 'maxNum' => 4]);
		$targetDataMap = new DataMap();
		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->optProp('maxNum', Mappers::factoryClosure(function (BindContext $bindContext) {
					$minNum = $bindContext->getValue('minNum', false);
					if ($minNum > $bindContext->getValue('maxNum', false)) {
						return Mappers::valueClosure(fn () => $minNum);
					}
					return Mappers::int(false, $bindContext->getValue('minNum', false) ?? 0);
				}))
				->optProp('minNum', Mappers::int())
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(6, $targetDataMap->req('minNum'));
		$this->assertEquals(6, $targetDataMap->req('maxNum'));
	}

}