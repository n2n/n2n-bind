<?php
namespace n2n\bind\build\impl\target;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;

class ObjectBindableTargetTest extends TestCase {
	public function testWrite() {
		$obj = new BindTestClass();
		$objToWrite = new BindTestClass();
		$arrToWrite = ['int' => 1, 'string' => 'hello', 'arr' => [], 'obj' => $objToWrite];

		Bind::attrs(['string' => 'test', 'int' => 123, 'array' => $arrToWrite, 'obj' => $objToWrite])
				->toObj($obj)
				->props(['string', 'int', 'array', 'obj'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $obj->getString());
		$this->assertEquals( 123, $obj->getInt());
		$this->assertEquals($arrToWrite, $obj->getArray());
		$this->assertEquals($objToWrite, $obj->getObj());
	}

	public function testWriteSomeProps() {
		$obj = new BindTestClass();

		Bind::values(...['string' => 'test'])
				->to(new ObjectBindableTarget($obj))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $obj->getString());
	}

	public function testExceptionUnknownProperty() {
		$this->expectException(BindTargetException::class);
		Bind::values(doesntExist: '')
				->to(new ObjectBindableTarget(new BindTestClass()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	public function testExceptionPropertyNotAccessible() {
		$this->expectException(BindTargetException::class);
		Bind::values(unaccessible: '')
				->to(new ObjectBindableTarget(new BindTestClass()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	public function testExceptionIncompatibleTypes() {
		$this->expectException(BindTargetException::class);
		Bind::values(obj: '123')
				->to(new ObjectBindableTarget(new BindTestClass()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}
}