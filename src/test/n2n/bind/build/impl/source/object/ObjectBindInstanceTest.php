<?php
namespace n2n\bind\build\impl\source\object;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindException;
use n2n\util\type\attrs\DataMap;

class ObjectBindInstanceTest extends TestCase {

	/**
	 * Helper method to create a dummy object with nested properties.
	 *
	 * The dummy object has:
	 * - Simple properties: 'firstname', 'lastname'
	 * - A nested object in property 'obj2' with its own 'firstname' and 'lastname'
	 * - A DataMap property 'dataMap' containing a nested array structure.
	 *
	 * @return object
	 */
	private function createDummyObject(): object {
		return new class {
			public string $firstname = 'Testerich';
			public string $lastname  = 'von Testen';
			public $obj2;
			public DataMap $dataMap;
			public $nonObject = 'I am not an object';

			public function __construct() {
				$this->obj2 = new class {
					public string $firstname = 'ChildFirst';
					public string $lastname  = 'ChildLast';
				};

				$this->dataMap = new DataMap(['childMap' => ['childProp' => 'hello']]);
			}
		};
	}

	/**
	 * Test that an empty attribute path returns a bindable containing the whole object.
	 */
	public function testCreateBindableWithEmptyPath(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$emptyPath = new AttributePath([]);
		$bindable = $instance->createBindable($emptyPath, true);

		$this->assertTrue($bindable->doesExist(), 'Bindable should exist for an empty path.');
		$this->assertSame($dummy, $bindable->getValue(), 'Bindable value should be the entire object.');
	}

	/**
	 * Test that a simple property is bound correctly.
	 */
	public function testCreateBindableSimpleProperty(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['firstname']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist());
		$this->assertEquals('Testerich', $bindable->getValue());
	}

	/**
	 * Test that a nested property is resolved correctly.
	 */
	public function testCreateBindableNestedProperty(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['obj2', 'firstname']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist());
		$this->assertEquals('ChildFirst', $bindable->getValue());
	}

	/**
	 * Test that when a property does not exist and mustExist is true,
	 * createBindable throws an exception.
	 */
	public function testCreateBindableNonExistentMustExist(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['nonexistent']);
		$this->expectException(UnresolvableBindableException::class);
		$instance->createBindable($path, true);
	}

	/**
	 * Test that when a property does not exist and mustExist is false,
	 * createBindable returns a bindable with a null value.
	 */
	public function testCreateBindableNonExistentNotMustExist(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['nonexistent']);
		$bindable = $instance->createBindable($path, false);

		$this->assertFalse($bindable->doesExist());
		$this->assertNull($bindable->getValue());
	}

	/**
	 * Test that nested property access fails if an intermediate property is not an object.
	 *
	 * In this test, we set the property "obj2" to a non-object value and then attempt to access a nested property.
	 */
	public function testCreateBindableNestedPropertyWithNonObjectIntermediate(): void {
		$dummy = $this->createDummyObject();
		$dummy->obj2 = 'I am not an object';
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['obj2', 'firstname']);
		$this->expectException(BindException::class);

		$instance->createBindable($path, true);
	}

	/**
	 * Test that DataMap property access works correctly.
	 *
	 * Given a DataMap in the dummy object with structure:
	 * [ 'childMap' => [ 'childProp' => 'hello' ] ],
	 * a path of ['dataMap', 'childMap', 'childProp'] should resolve to "hello".
	 */
	public function testDataMapAccessesHello(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		$path = new AttributePath(['dataMap', 'childMap', 'childProp']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist(), "Bindable should exist for DataMap property path.");
		$this->assertEquals('hello', $bindable->getValue(), "Bindable should return 'hello' from DataMap.");
	}

	/**
	 * Test that mixed data structures (objects, arrays, and DataMaps)
	 * are traversed correctly.
	 */
	public function testMixedStructures(): void {
		$dummy = new DummyMix();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindInstance($dummy, $proxyCache);

		// Case 1: object → object → DataMap → array, yielding "v1"
		$path1 = new AttributePath(['mix', 'case1', 'child', 'dataMap', 'arr', 'val']);
		$bindable1 = $instance->createBindable($path1, true);
		$this->assertTrue($bindable1->doesExist(), "Case 1: Bindable should exist for path 'mix/case1/child/dataMap/arr/val'.");
		$this->assertEquals('v1', $bindable1->getValue(), "Case 1: Expected value 'v1'.");

		// Case 2: array → DataMap → object, yielding "v2"
		$path2 = new AttributePath(['mix', 'case2', 'dm', 'child', 'name']);
		$bindable2 = $instance->createBindable($path2, true);
		$this->assertTrue($bindable2->doesExist(), "Case 2: Bindable should exist for path 'mix/case2/dm/child/name'.");
		$this->assertEquals('v2', $bindable2->getValue(), "Case 2: Expected value 'v2'.");

		// Case 3: object → array → DataMap, yielding "v3"
		$path3 = new AttributePath(['mix', 'case3', 'arr', 'info', 'key']);
		$bindable3 = $instance->createBindable($path3, true);
		$this->assertTrue($bindable3->doesExist(), "Case 3: Bindable should exist for path 'mix/case3/arr/info/key'.");
		$this->assertEquals('v3', $bindable3->getValue(), "Case 3: Expected value 'v3'.");

		// Case 4: array → array → DataMap, yielding "v4"
		$path4 = new AttributePath(['mix', 'case4', 'first', 'dm', 'key']);
		$bindable4 = $instance->createBindable($path4, true);
		$this->assertTrue($bindable4->doesExist(), "Case 4: Bindable should exist for path 'mix/case4/first/dm/key'.");
		$this->assertEquals('v4', $bindable4->getValue(), "Case 4: Expected value 'v4'.");

		// Case 5: object → DataMap → array → object, yielding "v5"
		$path5 = new AttributePath(['mix', 'case5', 'dataMap', 'arr', 'child', 'name']);
		$bindable5 = $instance->createBindable($path5, true);
		$this->assertTrue($bindable5->doesExist(), "Case 5: Bindable should exist for path 'mix/case5/dataMap/arr/child/name'.");
		$this->assertEquals('v5', $bindable5->getValue(), "Case 5: Expected value 'v5'.");

		// Case 6: DataMap → array → object, yielding "v6"
		$path6 = new AttributePath(['mix', 'case6', 'arr', 'child', 'name']);
		$bindable6 = $instance->createBindable($path6, true);
		$this->assertTrue($bindable6->doesExist(), "Case 6: Bindable should exist for path 'mix/case6/arr/child/name'.");
		$this->assertEquals('v6', $bindable6->getValue(), "Case 6: Expected value 'v6'.");

		// Case 7: DataMap → DataMap → object, yielding "v7"
		$path7 = new AttributePath(['mix', 'case7', 'dm', 'child', 'name']);
		$bindable7 = $instance->createBindable($path7, true);
		$this->assertTrue($bindable7->doesExist(), "Case 7: Bindable should exist for path 'mix/case7/dm/child/name'.");
		$this->assertEquals('v7', $bindable7->getValue(), "Case 7: Expected value 'v7'.");
	}
}

/* Dummy classes for testing purposes */
class DummyMix {
	public array $mix;
	public function __construct() {
		$this->mix = [
				'case1' => new Case1Parent(),
				'case2' => [
						'dm' => new DataMap(['child' => new Case2Child('v2')])
				],
				'case3' => new Case3Parent(),
				'case4' => [
						'first' => [
								'dm' => new DataMap(['key' => 'v4'])
						]
				],
				'case5' => new Case5Parent(),
				'case6' => new DataMap(['arr' => ['child' => new Case6Child()]]),
				'case7' => new DataMap(['dm' => new DataMap(['child' => new Case7Child()])]),
		];
	}
}

class Case1Parent {
	public Case1Child $child;
	public function __construct() {
		$this->child = new Case1Child();
	}
}

class Case1Child {
	public DataMap $dataMap;
	public function __construct() {
		$this->dataMap = new DataMap(['arr' => ['val' => 'v1']]);
	}
}

class Case2Child {
	public string $name;
	public function __construct(string $name) {
		$this->name = $name;
	}
}

class Case3Parent {
	public array $arr;
	public function __construct() {
		$this->arr = ['info' => new DataMap(['key' => 'v3'])];
	}
}

class Case5Child {
	public string $name;
	public function __construct() {
		$this->name = 'v5';
	}
}

class Case5Parent {
	public DataMap $dataMap;
	public function __construct() {
		$this->dataMap = new DataMap(['arr' => ['child' => new Case5Child()]]);
	}
}

class Case6Child {
	public string $name;
	public function __construct() {
		$this->name = 'v6';
	}
}

class Case7Child {
	public string $name;
	public function __construct() {
		$this->name = 'v7';
	}
}