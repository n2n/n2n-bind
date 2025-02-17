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
use n2n\util\DateUtils;

class DateSqlMapperTest extends TestCase {
	protected function setUp(): void {

	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$dateTime1 = new \DateTime();
		$dateTime2 = new \DateTime('2010-01-01');
		$dateTime2->setTime(8,9,10);
		$dateTimeImmutable1 = new \DateTimeImmutable('+7 days');
		$dateTimeImmutable1->setTime(11,12,13);


		$sourceDataMap = new DataMap(['DateTime1' => $dateTime1, 'DateTime2' => $dateTime2,
				'DateTimeImmutable1' => $dateTimeImmutable1, 'Null' => null]);
		$toDataMap = new DataMap();

		$result = Bind::attrs($sourceDataMap)->toAttrs($toDataMap)
				->props(['DateTime1', 'DateTime2', 'DateTimeImmutable1', 'Null'], Mappers::dateSql())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals($dateTime1->format(DateUtils::SQL_DATE_FORMAT), $toDataMap->reqString('DateTime1'));
		$this->assertEquals('2010-01-01', $toDataMap->reqString('DateTime2'));
		$this->assertEquals($dateTimeImmutable1->format(DateUtils::SQL_DATE_FORMAT), $toDataMap->reqString('DateTimeImmutable1'));
		$this->assertEquals(null, $toDataMap->reqString('Null', true));
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsFailsExpectExceptionBecauseInputIsNotDateTimeInterface() {
		$this->expectException(BindMismatchException::class);
		$sourceDataMap = new DataMap(['date' => '2010-01-02']);
		$toDataMap = new DataMap();

		Bind::attrs($sourceDataMap)->toAttrs($toDataMap)->props(['date'], Mappers::dateSql())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}



}
