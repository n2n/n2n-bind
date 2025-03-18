<?php

namespace n2n\bind\build\impl\plan;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\plan\BindContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;

class BindBoundaryTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testGetValue()	{
		Bind::attrs(['key1' => 'value1', 'key2' => 'value2'])
				->prop('key2', Mappers::valueClosure(function ($value, BindContext $bindContext) {
					$this->assertEquals('value2', $bindContext->getValue('key2'));
					return $value . '-mapped';
				}))
				->toArray()
				->exec();

	}


}