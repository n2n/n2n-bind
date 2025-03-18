<?php

namespace n2n\bind\build\impl\plan;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\plan\BindContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\AttributePath;
use n2n\util\type\attrs\DataMap;

class BindBoundaryTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testGetValue()	{
		$result = Bind::attrs(['key1' => 'value1', 'key2' => 'value2'])
				->prop('key1', Mappers::valueClosure(function ($value, BindContext $bindContext) {
					$bindInstance = $bindContext->unwarpBindInstance();
					$this->assertCount(1, $bindInstance->getBindables());
					$this->assertEquals('value2', $bindContext->getValue('key2'));
					$this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $bindContext->getValue());

					$this->assertCount(3, $bindInstance->getBindables());
					$this->assertTrue($bindInstance->getBindable(new AttributePath([]))->isLogical());
					$this->assertFalse($bindInstance->getBindable(new AttributePath(['key1']))->isLogical());
					$this->assertTrue($bindInstance->getBindable(new AttributePath(['key2']))->isLogical());

					return $value . '-mapped';
				}))
				->toArray()
				->exec();

		$this->assertTrue($result->isValid());
		$this->assertEquals(['key1' => 'value1-mapped'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testGetAbsoluteValue()	{
		$result = Bind::attrs(['key' => ['key1' => 'value1', 'key2' => 'value2']])
				->logicalProp('key', Mappers::subProps()
						->prop('key1', Mappers::valueClosure(function ($value, BindContext $bindContext) {
							$bindInstance = $bindContext->unwarpBindInstance();
							$this->assertCount(2, $bindInstance->getBindables());
							$this->assertEquals('value2', $bindContext->getValueByAbsolutePath('key/key2'));
							$this->assertEquals(['key1' => 'value1', 'key2' => 'value2'],
									$bindContext->getValueByAbsolutePath('key'));
							$this->assertEquals(['key' => ['key1' => 'value1', 'key2' => 'value2']],
									$bindContext->getValueByAbsolutePath());

							$this->assertCount(4, $bindInstance->getBindables());
							$this->assertTrue($bindInstance->getBindable(new AttributePath([]))->isLogical());
							$this->assertTrue($bindInstance->getBindable(new AttributePath(['key']))->isLogical());
							$this->assertFalse($bindInstance->getBindable(new AttributePath(['key', 'key1']))->isLogical());
							$this->assertTrue($bindInstance->getBindable(new AttributePath(['key', 'key2']))->isLogical());

							return $value . '-mapped';
						})))
				->toAttrs(new DataMap())
				->exec();

		$this->assertTrue($result->isValid());
		$this->assertEquals(['key' => ['key1' => 'value1-mapped']], $result->get()->toArray());
	}


}