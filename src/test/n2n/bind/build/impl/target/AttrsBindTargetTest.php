<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\build\impl\Bind;
use n2n\util\type\attrs\DataMap;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;

class AttrsBindTargetTest extends TestCase {


	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testClosure(): void {
		$bindTask = Bind::attrs()->prop('prop')->toAttrs(fn () => new DataMap());
		$dataMap1 = $bindTask->exec(input: ['prop' => 'value1'])->get();
		$dataMap2 = $bindTask->exec(input: ['prop' => 'value2'])->get();

		$this->assertNotSame($dataMap1, $dataMap2);

		$this->assertEquals('value1', $dataMap1->req('prop'));
		$this->assertEquals('value2', $dataMap2->req('prop'));
	}
}