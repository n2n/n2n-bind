<?php
namespace n2n\bind\build\impl\target;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\build\impl\target\mock\BindTestClassA;
use n2n\util\type\attrs\DataMap;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\MapResult;

class ObjectBindTargetTest extends TestCase {
	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testWrite() {
		$obj = new BindTestClassA();
		$objToWrite = new BindTestClassA();
		$arrToWrite = ['int' => 1, 'string' => 'hello', 'arr' => [], 'a' => $objToWrite];

		Bind::attrs(['string' => 'test', 'int' => 123, 'array' => $arrToWrite, 'a' => $objToWrite])
				->toObj($obj)
				->props(['string', 'int', 'array', 'a'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $obj->getString());
		$this->assertEquals( 123, $obj->getInt());
		$this->assertEquals($arrToWrite, $obj->getArray());
		$this->assertEquals($objToWrite, $obj->getA());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testWriteWithRepeatingNameParts() {
		$obj = new BindTestClassA();
		$arrToWrite = ['int' => 1, 'string' => 'hello', 'arr' => []];

		Bind::attrs(new DataMap(['string' => 'test', 'int' => 123, 'array' => $arrToWrite,
				'a' => [
					'int' => 234
				],
				'b' => [
					'value' => 'asdf'
				]
		]))->toObj($obj)
				->optProps(['string', 'int', 'array', 'a/int', 'b/value'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $obj->getString());
		$this->assertEquals( 123, $obj->getInt());
		$this->assertEquals($arrToWrite, $obj->getArray());
		$this->assertEquals(234, $obj->getA()->getInt());
		$this->assertEquals('asdf', $obj->b->value);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testWriteSomeProps() {
		$obj = new BindTestClassA();

		Bind::values(...['string' => 'test'])
				->to(new ObjectBindTarget($obj))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $obj->getString());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testExceptionUnknownProperty() {
		$this->expectException(BindTargetException::class);
		Bind::values(doesntExist: '')
				->to(new ObjectBindTarget(new BindTestClassA()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function testExceptionPropertyNotAccessible() {
		$this->expectException(BindTargetException::class);
		Bind::values(unaccessible: '')
				->to(new ObjectBindTarget(new BindTestClassA()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function testExceptionIncompatibleTypes() {
		$this->expectException(BindTargetException::class);
		Bind::values(obj: '123')
				->to(new ObjectBindTarget(new BindTestClassA()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testEmptyObject() {
		$a = new BindTestClassA();
		Bind::values(...['string' => 'test'])
				->to(new ObjectBindTarget($a))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('test', $a->getString());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testNestedProperties() {
		$obj = new BindTestClassA();
		$nestedObj = new BindTestClassA();

		Bind::attrs(['a' => ['a' => $nestedObj]])
				->toObj($obj)
				->props(['a/a'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals($nestedObj, $obj->getA()->getA());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testNestedPropertyDoesNotExist() {
		$this->expectException(BindTargetException::class);
		$obj = new BindTestClassA();

		Bind::attrs(['a' => ['doesntExist' => 'test']])
				->toObj($obj)
				->props(['a/doesntExist'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function testNonArrayProvidedWhenArrayExpected() {
		$this->expectException(BindTargetException::class);
		$obj = new BindTestClassA();

		Bind::attrs(['array' => 'notAnArray'])
				->toObj($obj)
				->props(['array'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws TaskInputMismatchException
	 * @throws BindTargetException
	 */
	function testObjectChildWrite(): void {
		$obj = new BindTestClassA();

		Bind::attrs(['bb' => ['value' => 'huii!']])
				->toObj($obj)
				->props(['bb/value'])
 				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('huii!', $obj->getBb()->value);
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testObjectNullChildWrite(): void {
		$this->expectException(BindTargetException::class);
		$obj = new BindTestClassA();

		Bind::attrs(['bbb' => ['value' => 'huii!']])
				->toObj($obj)
				->props(['bbb/value'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testObjectNullCreateChildWrite(): void {
		$obj = new BindTestClassA();

		Bind::attrs(['bbbb' => ['value' => 'huii!']])
				->toObj($obj)
				->props(['bbbb/value'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('huii!', $obj->getBbbb()->value);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testObjectChildGet(): void {
		$a = new BindTestClassA();

		Bind::attrs(['bb' => ['value' => 'huii!', 'value2' => 'holeradio']])
				->toObj($a)
				->props(['bb/value', 'bb/value2'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals(1, $a->getBbCallsCount);
		$this->assertEquals('huii!', $a->getBb()->value);
		$this->assertEquals('holeradio', $a->getBb()->getValue2());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testInaccessibleObjProp(): void {
		$this->expectException(BindTargetException::class);

		$a = new BindTestClassA();

		Bind::attrs(['inaccessibleB' => ['value' => 'huii!']])
				->toObj($a)
				->props(['inaccessibleB/value'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testNonObjProp(): void {
		$this->expectException(BindTargetException::class);

		$a = new BindTestClassA();

		Bind::attrs(['string' => ['value' => 'huii!']])
				->toObj($a)
				->props(['string/value'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testIgnoreRoot(): void {
		$dataMap = new DataMap(['holeradio' => 'foo']);
		$targetMock = new ObjTargetMock();

		$this->expectException(BindTargetException::class);

		$mapperMock =  $this->createMock(Mapper::class);
		$mapperMock->expects($this->once())->method('map')->willReturn(new MapResult(true));

		Bind::attrs($dataMap)->toObj($targetMock)
				->root($mapperMock)
				->exec($this->createMock(MagicContext::class));
	}

	public function testWriteWithClosure() {
		$arrToWrite = ['int' => 42, 'string' => 'closureTest', 'arr' => ['a', 'b']];

		$result = Bind::attrs(['string' => 'closureTest', 'int' => 42, 'array' => $arrToWrite])
				->toObj(fn() => new BindTestClassA())
				->props(['string', 'int', 'array'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$targetObj = $result->get();
		$this->assertInstanceOf(BindTestClassA::class, $targetObj);
		$this->assertEquals('closureTest', $targetObj->getString());
		$this->assertEquals(42, $targetObj->getInt());
		$this->assertEquals($arrToWrite, $targetObj->getArray());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	public function testClosureNonObject() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/return value must be of type object/');
		Bind::attrs(['string' => 'test'])
				->toObj(fn() => 'not an object')
				->props(['string'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	public function testMultipleExecWithClosure() {
		$closure = function() {
			return new BindTestClassA();
		};
		$result1 = Bind::attrs(['string' => 'first', 'int' => 1])
				->toObj($closure)
				->props(['string', 'int'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$target1 = $result1->get();
		$this->assertInstanceOf(BindTestClassA::class, $target1);
		$this->assertEquals('first', $target1->getString());
		$this->assertEquals(1, $target1->getInt());

		$result2 = Bind::attrs(['string' => 'second', 'int' => 2])
				->toObj($closure)
				->props(['string', 'int'])
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$target2 = $result2->get();
		$this->assertInstanceOf(BindTestClassA::class, $target2);
		$this->assertEquals('second', $target2->getString());
		$this->assertEquals(2, $target2->getInt());

		$this->assertNotSame($target1, $target2);
	}
}

class ObjTargetMock {

	public ?string $holeradio = null;
}