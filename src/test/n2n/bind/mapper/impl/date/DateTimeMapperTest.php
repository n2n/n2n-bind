<?php

namespace n2n\bind\mapper\impl\date;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\magic\TaskResult;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\validation\plan\ErrorMap;
use n2n\l10n\N2nLocale;

class DateTimeMapperTest extends TestCase {
	private DataMap $sdm;
	private DataMap $tdm;
	private \DateTime|\DateTimeImmutable $min;
	private \DateTime|\DateTimeImmutable $max;
	private \DateTime $validDate;
	private \DateTime $beforeMinDate;
	private \DateTime $afterMaxDate;

	protected function setUp(): void {
		$this->validDate = new \DateTime('2010-01-01');
		$this->beforeMinDate = new \DateTime('2009-12-30');
		$this->afterMaxDate = new \DateTime('2010-01-03');

		$this->sdm = new DataMap(['date' => $this->validDate]);
		$this->tdm = new DataMap();
		$this->min = new \DateTime('2009-12-31');
		$this->max = new \DateTimeImmutable('2010-01-02');
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeWithinBoundaries(): void {
		$result = $this->performMapping();

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeBeforeMin(): void {
		$this->sdm->set('date', $this->beforeMinDate);
		$result = $this->performMapping();

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeAfterMax(): void {
		$this->sdm->set('date', $this->afterMaxDate);
		$result = $this->performMapping();

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeEqualsMinMax(): void {
		$this->min = $this->max = new \DateTime('2010-01-01');
		$result = $this->performMapping();

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws \Exception
	 */
	public function testDateTimeStringConversion(): void {
		$this->sdm->set('date', '2010-01-01 00:00:00');
		$result = $this->performMapping();

		$this->assertTrue($result->isValid());
		$this->assertInstanceOf(\DateTime::class, $this->tdm->req('date'));
		$this->assertEquals(new \DateTime($this->sdm->req('date')), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeStringConversionInvalid(): void {
		$this->sdm->set('date', 'wrong_format');
		$result = $this->performMapping();

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testDateTimeImmutableWithinBoundaries(): void {
		$this->sdm->set('date', new \DateTimeImmutable('2010-01-01'));
		$result = $this->performMappingImmutable();

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeImmutableBeforeMin(): void {
		$this->sdm->set('date', new \DateTimeImmutable('2008-12-30'));
		$result = $this->performMappingImmutable(false, new \DateTimeImmutable('2009-01-01'));

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeImmutableAfterMax(): void {
		$this->sdm->set('date', new \DateTimeImmutable('2010-01-03'));
		$result = $this->performMappingImmutable(max: new \DateTimeImmutable('2010-01-02'));

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeImmutableEqualsMinMax(): void {
		$this->sdm->set('date', new \DateTimeImmutable('2010-01-01'));
		$this->min = $this->max = new \DateTimeImmutable('2010-01-01');
		$result = $this->performMappingImmutable();

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
		$this->assertInstanceOf(\DateTimeImmutable::class, $this->tdm->req('date'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws \Exception
	 */
	public function testDateTimeImmutableStringConversion(): void {
		$this->sdm->set('date', '2010-01-01 00:00:00');
		$result = $this->performMappingImmutable();

		$this->assertTrue($result->isValid());
		$this->assertInstanceOf(\DateTimeImmutable::class, $this->tdm->req('date'));
		$this->assertEquals(new \DateTimeImmutable($this->sdm->req('date')), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeImmutableStringConversionInvalid(): void {
		$this->sdm->set('date', 'wrong_format');
		$result = $this->performMappingImmutable();

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableDisabled(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(false);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableDisabledWithMinMax(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(false, $this->min, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableDisabledWithMinNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(false, null, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableDisabledWithMaxNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(false, $this->min, null);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableEnabled(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(true);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableEnabledWithMinMax(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(true, $this->min, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableEnabledWithMinNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(true, null, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateTimeMapperNullableEnabledWithMaxNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(true, $this->min, null);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeMapperNullableEnabledWithBothNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMapping(true, null, null);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableDisabled(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(false);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableDisabledWithMinMax(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(false, $this->min, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableDisabledWithMinNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(false, null, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableDisabledWithMaxNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(false, $this->min, null);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableEnabled(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(true);

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableEnabledWithMinMax(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(true, $this->min, $this->max);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableEnabledWithMinNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(true, null, $this->max);

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->req('date'));
	}

	public function testDateTimeImmutableMapperNullableEnabledWithMaxNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(true, $this->min, null);

		$this->assertFalse($result->isValid());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeImmutableMapperNullableEnabledWithBothNull(): void {
		$this->sdm->set('date', null);

		$result = $this->performMappingImmutable(true, null, null);

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->req('date'));
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	private function performMapping(bool $nullable = true, $min = null, $max = null): TaskResult {
		$min = $min ?? $this->min;
		$max = $max ?? $this->max;

		return Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->optProp('date', Mappers::dateTime(!$nullable, $min, $max))
				->exec($this->createMock(MagicContext::class));
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	private function performMappingImmutable(bool $nullable = true, $min = null, $max = null): TaskResult {
		return Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->optProp('date', Mappers::dateTimeImmutable(!$nullable, $min, $max))
				->exec($this->createMock(MagicContext::class));
	}
}