<?php

namespace n2n\bind\mapper\impl\mod;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;

class ValueToSubValuesMapperTest extends TestCase {

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testClosure(): void {
		$result = Bind::attrs(['prop1' => 'value1', 'prop2' => 'value2'])
				->prop('prop1', Mappers::valueToSubValues(function (string $value) {
					$this->assertSame('value1', $value);
					return ['subProp1' => 'subValue1', 'subProp2' => 'subValue2'];
				}))
				->prop('prop2')
				->toArray()
				->exec();

		$this->assertSame(
				['prop1/subProp1' => 'subValue1', 'prop1/subProp2' => 'subValue2', 'prop2' => 'value2'],
				$result->get());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testArray(): void {
		$result = Bind::attrs(['prop1' => 'value1', 'prop2' => 'value2'])
				->prop('prop1', Mappers::valueToSubValues(['subProp1' => 'subValue1', 'subProp2' => 'subValue2']))
				->prop('prop2')
				->toArray()
				->exec();

		$this->assertSame(
				['prop1/subProp1' => 'subValue1', 'prop1/subProp2' => 'subValue2', 'prop2' => 'value2'],
				$result->get());
	}

}