<?php

namespace n2n\bind\mapper\impl\date;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\util\calendar\Time;

class TimeSqlMapperTest extends TestCase {

	protected function setUp(): void {
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testTimeSqlMapperNull(): void {
		$sdm = new DataMap(['time' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['time'], Mappers::timeSql())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertNull($tdm->req('time'));
	}


	/**
	 * @throws BindMismatchException
	 * @throws BindTargetException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	public function testTimeSqlMapper(): void {
		$time = new Time('10:11:12');
		$sdm = new DataMap(['time' => $time]);
		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['time'], Mappers::timeSql())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($time->__toString(), $tdm->req('time'));
	}

	public function testTimeSqlMapperInvalid(): void {
		$this->expectException(BindMismatchException::class);
		$time = new \DateTime('now');
		$sdm = new DataMap(['time' => $time]);
		$tdm = new DataMap();
		Bind::attrs($sdm)->toAttrs($tdm)
				->props(['time'], Mappers::timeSql())
				->exec($this->createMock(MagicContext::class));
	}
}
