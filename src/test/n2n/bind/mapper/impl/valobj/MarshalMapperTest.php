<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\bind\build\impl\Bind;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\mapper\impl\valobj\mock\ObjectUnmarshalMock;
use n2n\bind\mapper\impl\valobj\mock\ObjectUnmarshalWithSpecialMappingMock;
use n2n\bind\mapper\impl\valobj\mock\TypedArrayMarshalUnmarshalMock;

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

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testUnmarshalObjectUnmarshalTrait(): void {
		$result = Bind::attrs(['name' => 'John Doe', 'age' => 30])
				->root(Mappers::unmarshal(ObjectUnmarshalMock::class))
				->toValue()
				->exec($this->createMock(MagicContext::class));

		$obj = $result->get();
		$this->assertInstanceOf(ObjectUnmarshalMock::class, $obj);
		$this->assertEquals('John Doe', $obj->name);
		$this->assertEquals(30, $obj->age);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testUnmarshalObjectWithSpecialMapping(): void {
		$result = Bind::attrs(['name' => 'John Doe', 'age' => 30])
				->root(Mappers::unmarshal(ObjectUnmarshalWithSpecialMappingMock::class))
				->toValue()
				->exec($this->createMock(MagicContext::class));

		$obj = $result->get();
		$this->assertInstanceOf(ObjectUnmarshalWithSpecialMappingMock::class, $obj);
		$this->assertEquals('John Doe', $obj->name);
		$this->assertEquals(999999, $obj->age);
	}

	function testTypedArrayMarshalTrait(): void {
		$this->markTestSkipped('Not yet implemented');

//		$mock = new TypedArrayMarshalUnmarshalMock([new ValueObjectMock('em@ail.ch')]);
//
//		$result = Bind::values($mock)
//				->map(Mappers::marshal())
//				->toArray()
//				->exec($this->createMock(MagicContext::class));
//
//		$array = $result->get();
//
//		$this->assertTrue(is_array($array));
//		$this->assertEquals('John Doe', $array['name']);
//		$this->assertEquals(30, $array['age']);
	}


	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testTypedArrayUnmarshalTrait(): void {
		$result = Bind::attrs(['emails' => ['email1@holeradio.ch', 'email2@holeradio.ch']])
				->prop('emails', Mappers::unmarshal(TypedArrayMarshalUnmarshalMock::class))
				->toValue()
				->exec($this->createMock(MagicContext::class));

		$obj = $result->get();
		$this->assertInstanceOf(TypedArrayMarshalUnmarshalMock::class, $obj);
		$this->assertEquals(
				[new ValueObjectMock('email1@holeradio.ch'), new ValueObjectMock('email2@holeradio.ch')],
				$obj->toArray());
	}
}