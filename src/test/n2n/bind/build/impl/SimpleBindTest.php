<?php

namespace n2n\bind\build\impl;

use PHPUnit\Framework\TestCase;
use n2n\bind\mapper\impl\valobj\mock\ObjectUnmarshalMock;
use n2n\util\magic\MagicContext;
use n2n\util\attr\DataMap;

class SimpleBindTest extends TestCase {

	function testUnmarshalAttrs(): void {
		$result = SimpleBind::unmarshalAttrs(ObjectUnmarshalMock::class, ['name' => 'John Doe', 'age' => 30])
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$obj = $result->get();
		$this->assertInstanceOf(ObjectUnmarshalMock::class, $obj);
		$this->assertEquals('John Doe', $obj->name);
		$this->assertEquals(30, $obj->age);
	}

	function testUnmarshalAttrsInput(): void {
		$task = SimpleBind::unmarshalAttrs(ObjectUnmarshalMock::class);

		$obj = $task->exec(input: new DataMap(['name' => 'Jane Doe', 'age' => 25]))->get();
		$this->assertInstanceOf(ObjectUnmarshalMock::class, $obj);
		$this->assertEquals('Jane Doe', $obj->name);
		$this->assertEquals(25, $obj->age);
	}
}
