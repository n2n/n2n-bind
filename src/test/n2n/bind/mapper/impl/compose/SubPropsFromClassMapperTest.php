<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\impl\compose\mock\SimpleBaseRecord;
use n2n\util\type\custom\Undefined;
use n2n\bind\mapper\impl\compose\mock\KnownTypesRecord;
use n2n\util\uri\Url;
use n2n\util\calendar\Date;
use n2n\util\calendar\Time;
use n2n\bind\mapper\impl\valobj\ValueObjectMock;
use n2n\bind\mapper\impl\compose\mock\ValObjRecord;
use n2n\bind\mapper\impl\compose\mock\SubBaseRecord;
use n2n\bind\mapper\impl\compose\mock\AdvancedTypesRecord;
use n2n\util\type\mock\PureEnumMock;
use n2n\bind\mapper\impl\enum\mock\MockEnum;

class SubPropsFromClassMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testBaseAttrs() {
		$srcObj = new SimpleBaseRecord();
		$srcObj->prop = 'huii';
		$srcObj->nullableProp = null;
		$srcObj->undefNullableProp = Undefined::i();
		$srcObj->mixedProp = [];


		$targetAttrs = Bind::obj($srcObj)
				->logicalRoot(Mappers::subPropsFromClass(SimpleBaseRecord::class))
				->toArray()->exec()->get();

		$this->assertSame(
				['prop' => 'huii', 'nullableProp' => null, 'mixedProp' => []],
				$targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testBaseFail() {
		$this->expectException(BindMismatchException::class);

		$targetAttrs = Bind::attrs(['prop' => new \DateTimeImmutable()])
				->logicalRoot(Mappers::subPropsFromClass(SimpleBaseRecord::class))
				->toArray()->exec()->get();

	}

}