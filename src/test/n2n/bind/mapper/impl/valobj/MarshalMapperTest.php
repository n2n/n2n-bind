<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\bind\build\impl\Bind;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\Mappers;

class MarshalMapperTest extends TestCase  {


	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMarshal(): void {
		$values = [];
		Bind::values(new ValueObjectMock('test@email.ch'), null)->toArray($values)
				->map(Mappers::marshal())
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['test@email.ch', null], $values);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testUnmarshal(): void {
		$values = [];
		Bind::values('test@email.ch', null)->toArray($values)
				->map(Mappers::unmarshal(ValueObjectMock::class))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals([new ValueObjectMock('test@email.ch'), null], $values);
	}
}