<?php

namespace n2n\bind\mapper\impl\numeric;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class IntMapperTest extends TestCase {

	function testDocsUsage(): void {
		$result = Bind::values('42', null)->map(Mappers::int())->toArray()->exec();
		var_dump($result->get());

		$this->assertSame([42, null], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['age' => null, 'count' => 0, 'total' => 100])
				->props(['age', 'count', 'total'], Mappers::int(true, 20, 80))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('age')->isEmpty()); // false because mandatory
		var_dump($result->getErrorMap()->getChild('count')->isEmpty()); // false due to min
		var_dump($result->getErrorMap()->getChild('total')->isEmpty()); // false due to max

		$this->assertFalse($result->isValid());
		$this->assertEquals('Mandatory', $result->getErrorMap()->getChild('age')->jsonSerialize()['messages'][0]);
		$this->assertEquals('Min [min = 20]', $result->getErrorMap()->getChild('count')->jsonSerialize()['messages'][0]);
		$this->assertEquals('Max [max = 80]', $result->getErrorMap()->getChild('total')->jsonSerialize()['messages'][0]);
	}
}