<?php

namespace n2n\bind\mapper\impl\date;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindResult;

class DateTimeMapperTest extends TestCase {
	private $sdm;
	private $tdm;
	private $min;
	private $max;

	protected function setUp(): void {
		$this->sdm = new DataMap(['date' => new \DateTime('2010-01-01')]);
		$this->tdm = new DataMap();
		$this->min = new \DateTime('2009-12-31');
		$this->max = new \DateTime('2010-01-02');
	}

	public function testDateTimeWithinBoundaries(): void {
		$result = $this->performMapping();

		$this->assertTrue(!$result->hasErrors());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	public function testDateTimeBeforeMin(): void {
		$this->sdm->set('date', new \DateTime('2009-12-30'));
		$result = $this->performMapping();

		$this->assertTrue($result->hasErrors());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeAfterMin(): void {
		$this->sdm->set('date', new \DateTime('2010-01-03'));
		$result = $this->performMapping();

		$this->assertTrue($result->hasErrors());
		$this->assertNull($this->tdm->opt('date'));
	}

	public function testDateTimeEqualsMinMax(): void {
		$this->min = $this->max = new \DateTime('2010-01-01');
		$result = $this->performMapping();

		$this->assertTrue(!$result->hasErrors());
		$this->assertEquals($this->sdm->req('date'), $this->tdm->req('date'));
	}

	public function testDateTimeStringConversion(): void {
		$this->sdm->set('date', '2010-01-01 00:00:00');
		$result = $this->performMapping();

		$this->assertTrue(!$result->hasErrors());
		$this->assertEquals(new \DateTime($this->sdm->req('date')), $this->tdm->req('date'));
	}

	public function testDateTimeStringConversionInvalid(): void {
		$this->sdm->set('date', 'wrong_format');
		$result = $this->performMapping();

		$this->assertTrue($result->hasErrors());
		$this->assertNull($this->tdm->opt('date'));
	}

	private function performMapping(): BindResult {
		return Bind::attrs($this->sdm)->toAttrs($this->tdm)
				->props(['date'], Mappers::dateTime(true, $this->min, $this->max))
				->exec($this->createMock(MagicContext::class));
	}
}
