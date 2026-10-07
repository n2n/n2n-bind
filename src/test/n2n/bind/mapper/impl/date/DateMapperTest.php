<?php

namespace n2n\bind\mapper\impl\date;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\util\calendar\Date;
use n2n\util\attr\AttributesException;

class DateMapperTest extends TestCase {
	private DataMap $sdm;
	private DataMap $tdm;

	protected function setUp(): void {
		$this->sdm = new DataMap(['date' => new Date('2023-10-01')]);
		$this->tdm = new DataMap();
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 * this will test {@link DateMapper} without params
	 */
	public function testDateMapper(): void {
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testDateWithinBoundaries(): void {
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, new Date('2023-01-01'), new Date('2023-10-31')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateBeforeMin(): void {
		$this->sdm->set('date', new Date('2023-11-11'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, new Date('2023-12-12'), null))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertStringContainsString('Not Earlier Than Earliest', $result->getErrorMap()->getChild('date')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateBeforeMax(): void {
		$this->sdm->set('date', new Date('2001-01-20'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, null, new Date('2020-12-19')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateAfterMax(): void {
		$this->sdm->set('date', new Date('2023-12-12'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, null, new Date('2023-11-11')))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertStringContainsString('Not Later Than Latest', $result->getErrorMap()->getChild('date')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateEqualsMinMax(): void {
		$this->sdm->set('date', new Date('2023-10-01'));
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, new Date('2023-10-01'), new Date('2023-10-01')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testDateStringConversion(): void {
		$this->sdm->set('date', '2023-10-01');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date())
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertInstanceOf(Date::class, $this->tdm->req('date'));
		$this->assertEquals(new Date($this->sdm->req('date')), $this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDateStringConversionInvalid(): void {
		$this->sdm->set('date', '10:10:10');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->optProps(['date'], Mappers::date())
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertEquals('Date Format', $result->getErrorMap()->getChild('date')->jsonSerialize()['messages'][0]);
		$this->assertNull($this->tdm->opt('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testDateMapperNull(): void {
		$this->sdm->set('date', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->req('date'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	public function testDateMapperBoundariesNull(): void {
		$this->sdm->set('date', '2023-10-10');
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, null, null))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals(new Date('2023-10-10'), $this->tdm->req('date'));
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testDateMapperNullMandatory(): void {
		$this->sdm->set('date', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(true, null, null))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertEquals('Mandatory', $result->getErrorMap()->getChild('date')->jsonSerialize()['messages'][0]);
		$this->assertEmpty($this->tdm->toArray());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * @throws AttributesException
	 */
	public function testDateMapperNullMinMax(): void {
		$this->sdm->set('date', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(false, new Date('2023-10-01'), new Date('2023-10-01')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertNull($this->tdm->req('date'));
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testDateMapperNullMandatoryMinMax(): void {
		$this->sdm->set('date', null);
		$result = Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::date(true, new Date('2023-10-01'), new Date('2023-10-01')))
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$this->assertEquals('Mandatory', $result->getErrorMap()->getChild('date')->jsonSerialize()['messages'][0]);
		$this->assertEmpty($this->tdm->toArray());
	}

	public function testDocsUsage(): void {
		$result = Bind::values('2023-10-01', null)->map(Mappers::date())->toArray()->exec();
		var_dump($result->get());

		$this->assertTrue($result->isValid());
		$this->assertEquals([new Date('2023-10-01'), null], $result->get());
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	public function testDocsVal(): void {
		$result = Bind::attrs([
				'd1' => null,
				'd2' => new Date('2023-01-01'),
				'd3' => new Date('2025-01-01'),
			])
				->props(['d1', 'd2', 'd3'], Mappers::date(true, new Date('2023-06-01'), new Date('2024-06-01')))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all date properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('d1')->isEmpty()); // false because mandatory
		var_dump($result->getErrorMap()->getChild('d2')->isEmpty()); // false due to min
		var_dump($result->getErrorMap()->getChild('d3')->isEmpty()); // false due to max

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('d1')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('d2')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('d3')->isEmpty());
	}
}
