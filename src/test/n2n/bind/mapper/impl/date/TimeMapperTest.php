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

class TimeMapperTest extends TestCase {
	private DataMap $sdm;
	private DataMap $tdm;

	protected function setUp(): void {
		$this->sdm = new DataMap(['time' => new Time('14:30:00')]);
		$this->tdm = new DataMap();
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testTimeWithinBoundaries(): void {
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false, new Time('08:00:00'), new Time('22:45:00')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('time'), $this->tdm->req('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testTimeBeforeMin(): void {
		$this->sdm->set('time', new Time('06:00:00'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false, new Time('08:00:00'), null))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertStringContainsString('Not Earlier Than Earliest', $result->getErrorMap()->getChild('time')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testTimeAfterMax(): void {
		$this->sdm->set('time', new Time('23:00:00'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false, null, new Time('22:45:00')))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertStringContainsString('Not Later Than Latest', $result->getErrorMap()->getChild('time')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testTimeEqualsMinMax(): void {
		$this->sdm->set('time', new Time('14:30:00'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false, new Time('14:30:00'), new Time('14:30:00')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('time'), $this->tdm->req('time'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testTimeStringConversion(): void {
		$this->sdm->set('time', '11:59');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertInstanceOf(Time::class, $this->tdm->req('time'));
		$this->assertEquals(new Time($this->sdm->req('time')), $this->tdm->req('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testTimeStringConversionInvalid(): void {
		$this->sdm->set('time', '2010-01-02');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->optProps(['time'], Mappers::time())
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertEquals('Invalid', $result->getErrorMap()->getChild('time')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testTimeMapperNull(): void {
		$this->sdm->set('time', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->req('time'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testTimeMapperBoundariesNull(): void {
		$this->sdm->set('time', '23:30:00');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(false, null, null))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals(new Time('23:30:00'), $this->tdm->req('time'));
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testTimeMapperNullMandatory(): void {
		$this->sdm->set('time', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['time'], Mappers::time(true, null, null))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertEquals('Mandatory', $result->getErrorMap()->getChild('time')->jsonSerialize()['messages'][0]);
		$this->assertEmpty($this->tdm->toArray());
	}
}
