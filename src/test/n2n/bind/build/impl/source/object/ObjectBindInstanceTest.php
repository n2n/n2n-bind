<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\bind\build\impl\source\object;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\BindException;

class ObjectBindInstanceTest extends TestCase {

	/**
	 * Helper method to create a dummy object with nested properties.
	 *
	 * The dummy object has:
	 * - Simple properties: 'firstname', 'lastname'
	 * - A nested object in property 'obj2' with its own 'firstname' and 'lastname'
	 * - An ArrayObject property 'arrObj' containing a nested array structure.
	 *
	 * @return object
	 */
	private function createDummyObject(): object {
		return new class {
			public string $firstname = 'Testerich';
			public string $lastname  = 'von Testen';
			public $obj2;
			public \ArrayObject $arrObj;
			public $nonObject = 'I am not an object';

			public function __construct() {
				$this->obj2 = new class {
					public string $firstname = 'ChildFirst';
					public string $lastname  = 'ChildLast';
				};

				$this->arrObj = new \ArrayObject(['childMap' => ['childProp' => 'hello']]);
			}
		};
	}

	/**
	 * Test that an empty attribute path returns a bindable containing the whole object.
	 * @throws BindException
	 */
	public function testCreateBindableWithEmptyPath(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$emptyPath = new AttributePath([]);
		$bindable = $instance->createBindable($emptyPath, true);

		$this->assertTrue($bindable->doesExist(), 'Bindable should exist for an empty path.');
		$this->assertSame($dummy, $bindable->getValue(), 'Bindable value should be the entire object.');
	}

	/**
	 * Test that a simple property is bound correctly.
	 * @throws BindException
	 */
	public function testCreateBindableSimpleProperty(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['firstname']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist());
		$this->assertEquals('Testerich', $bindable->getValue());
	}

	/**
	 * Test that a nested property is resolved correctly.
	 * @throws BindException
	 */
	public function testCreateBindableNestedProperty(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['obj2', 'firstname']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist());
		$this->assertEquals('ChildFirst', $bindable->getValue());
	}

	/**
	 * Test that when a property does not exist and mustExist is true,
	 * createBindable throws an exception.
	 * @throws BindMismatchException
	 */
	public function testCreateBindableNonExistentMustExist(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['nonexistent']);
		$this->expectException(UnresolvableBindableException::class);
		$instance->createBindable($path, true);
	}

	/**
	 * Test that when a property does not exist and mustExist is false,
	 * createBindable returns a bindable with a null value and {@link Bindable::doesExist()} equals to false.
	 * @throws BindException
	 */
	public function testCreateBindableNonExistentNotMustExist(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['nonexistent']);
		$bindable = $instance->createBindable($path, false);

		$this->assertFalse($bindable->doesExist());
		$this->assertNull($bindable->getValue());
	}

	/**
	 * Test that nested property access fails if an intermediate property is not an object.
	 *
	 * In this test, we set the property "obj2" to a non-object value and then attempt to access a nested property.
	 * @throws BindMismatchException
	 */
	public function testCreateBindableNestedPropertyWithNonObjectIntermediate(): void {
		$dummy = $this->createDummyObject();
		$dummy->obj2 = 'I am not an object';
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['obj2', 'firstname']);
		$this->expectException(UnresolvableBindableException::class);

		$instance->createBindable($path, true);
	}

	/**
	 * Test that ArrayObject property access works correctly.
	 *
	 * Given an ArrayObject in the dummy object with structure:
	 * [ 'childMap' => [ 'childProp' => 'hello' ] ],
	 * a path of ['arrObj', 'childMap', 'childProp'] should resolve to "hello".
	 *
	 * @throws BindException
	 */
	public function testArrayObjectAccessesHello(): void {
		$dummy = $this->createDummyObject();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['arrObj', 'childMap', 'childProp']);
		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist(), 'Bindable should exist for ArrayObject property path.');
		$this->assertEquals('hello', $bindable->getValue(), 'Bindable should return \'hello\' from ArrayObject.');
	}

	/**
	 * Test that mixed data structures (objects, arrays, and ArrayObjects)
	 * are traversed correctly.
	 * @throws BindException
	 */
	public function testMixedStructures(): void {
		$dummy = new DummyMix();
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		// Case 1: object → object → ArrayObject → array, yielding "v1"
		$path1 = new AttributePath(['mix', 'case1', 'child', 'arrObj', 'arr', 'val']);
		$bindable1 = $instance->createBindable($path1, true);
		$this->assertTrue($bindable1->doesExist(), 'Case 1: Bindable should exist for path \'mix/case1/child/arrObj/arr/val\'.');
		$this->assertEquals('v1', $bindable1->getValue(), 'Case 1: Expected value \'v1\'.');

		// Case 2: array → ArrayObject → object, yielding "v2"
		$path2 = new AttributePath(['mix', 'case2', 'dm', 'child', 'name']);
		$bindable2 = $instance->createBindable($path2, true);
		$this->assertTrue($bindable2->doesExist(), 'Case 2: Bindable should exist for path \'mix/case2/dm/child/name\'.');
		$this->assertEquals('v2', $bindable2->getValue(), 'Case 2: Expected value \'v2\'.');

		// Case 3: object → array → ArrayObject, yielding "v3"
		$path3 = new AttributePath(['mix', 'case3', 'arr', 'info', 'key']);
		$bindable3 = $instance->createBindable($path3, true);
		$this->assertTrue($bindable3->doesExist(), 'Case 3: Bindable should exist for path \'mix/case3/arr/info/key\'.');
		$this->assertEquals('v3', $bindable3->getValue(), 'Case 3: Expected value \'v3\'.');

		// Case 4: array → array → ArrayObject, yielding "v4"
		$path4 = new AttributePath(['mix', 'case4', 'first', 'dm', 'key']);
		$bindable4 = $instance->createBindable($path4, true);
		$this->assertTrue($bindable4->doesExist(), 'Case 4: Bindable should exist for path \'mix/case4/first/dm/key\'.');
		$this->assertEquals('v4', $bindable4->getValue(), 'Case 4: Expected value \'v4\'.');

		// Case 5: object → ArrayObject → array → object, yielding "v5"
		$path5 = new AttributePath(['mix', 'case5', 'arrObj', 'arr', 'child', 'name']);
		$bindable5 = $instance->createBindable($path5, true);
		$this->assertTrue($bindable5->doesExist(), 'Case 5: Bindable should exist for path \'mix/case5/arrObj/arr/child/name\'.');
		$this->assertEquals('v5', $bindable5->getValue(), 'Case 5: Expected value \'v5\'.');

		// Case 6: ArrayObject → array → object, yielding "v6"
		$path6 = new AttributePath(['mix', 'case6', 'arr', 'child', 'name']);
		$bindable6 = $instance->createBindable($path6, true);
		$this->assertTrue($bindable6->doesExist(), 'Case 6: Bindable should exist for path \'mix/case6/arr/child/name\'.');
		$this->assertEquals('v6', $bindable6->getValue(), 'Case 6: Expected value \'v6\'.');

		// Case 7: ArrayObject → ArrayObject → object, yielding "v7"
		$path7 = new AttributePath(['mix', 'case7', 'dm', 'child', 'name']);
		$bindable7 = $instance->createBindable($path7, true);
		$this->assertTrue($bindable7->doesExist(), 'Case 7: Bindable should exist for path \'mix/case7/dm/child/name\'.');
		$this->assertEquals('v7', $bindable7->getValue(), 'Case 7: Expected value \'v7\'.');
	}

	/**
	 * Test that when a nested lookup fails because an ArrayAccess value is not traversable,
	 * the thrown exception message contains the full accumulated path.
	 */
	public function testExceptionMessageForArrayAccessNonTraversable(): void {
		$dummy = new class {
			public \ArrayObject $objArr;
			public function __construct() {
				$this->objArr = new \ArrayObject(['arr' => 'non-traversable']);
			}
		};
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['objArr', 'arr', 'child']);
		try {
			$instance->createBindable($path, true);
			$this->fail('Expected ValueNotTraversableException to be thrown.');
		} catch (UnresolvableBindableException $e) {
			$message = $e->getMessage();
			$this->assertEquals(
					'Can not resolve path "objArr/arr/child". Path "objArr/arr" resolved a value of type string which is not traversable. Traversable types are: object, array or \ArrayAccess.',
					$message);
		}
	}

	/**
	 * Test that when a key is missing in an array, the exception message contains the full path.
	 * @throws BindMismatchException
	 */
	public function testExceptionMessageForMissingKey(): void {
		$dummy = new class {
			public array $data = ['foo' => 'bar'];
		};
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['data', 'baz']);
		try {
			$instance->createBindable($path, true);
			$this->fail('Expected ValueNotTraversableException to be thrown.');
		} catch (UnresolvableBindableException $e) {
			$message = $e->getMessage();
			$this->assertEquals(
					'Can not resolve path "data/baz". Key "baz" does not exist in array resolved by path "data"',
					$message);
		}
	}

	/**
	 * Test that when an intermediate property is not an object (and so cannot be traversed),
	 * the exception message contains the full path.
	 * @throws BindMismatchException
	 */
	public function testExceptionMessageForNonObjectIntermediate(): void {
		$dummy = $this->createDummyObject();
		// Set obj2 to a non-object value
		$dummy->obj2 = 'I am not an object';
		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['obj2', 'firstname']);
		try {
			$instance->createBindable($path, true);
			$this->fail('Expected ValueNotTraversableException to be thrown.');
		} catch (UnresolvableBindableException $e) {
			$message = $e->getMessage();
			$this->assertEquals(
					'Can not resolve path "obj2/firstname". Path "obj2" resolved a value of type string which is not traversable. Traversable types are: object, array or \ArrayAccess.',
					$message);
		}
	}

	/**
	 * Test that a deep nested attribute path (10 levels) is traversed correctly.
	 *
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	public function testDeepNestedPath(): void {
		$dummy = new class {
			public $level1;
			public function __construct() {
				$this->level1 = new class {
					public $level2;
					public function __construct() {
						$this->level2 = new class {
							public $level3;
							public function __construct() {
								$this->level3 = new class {
									public $level4;
									public function __construct() {
										$this->level4 = new class {
											public $level5;
											public function __construct() {
												$this->level5 = new class {
													public $level6;
													public function __construct() {
														$this->level6 = new class {
															public $level7;
															public function __construct() {
																$this->level7 = new class {
																	public $level8;
																	public function __construct() {
																		$this->level8 = new class {
																			public $level9;
																			public function __construct() {
																				$this->level9 = new class {
																					public $level10;
																					public function __construct() {
																						$this->level10 = 'DeepValue';
																					}
																				};
																			}
																		};
																	}
																};
															}
														};
													}
												};
											}
										};
									}
								};
							}
						};
					}
				};
			}
		};

		$proxyCache = new ObjectBindAccessProxyCache();
		$instance = new ObjectBindableFactory($dummy, $proxyCache);

		$path = new AttributePath(['level1', 'level2', 'level3', 'level4', 'level5', 'level6', 'level7', 'level8',
				'level9', 'level10']);

		$bindable = $instance->createBindable($path, true);

		$this->assertTrue($bindable->doesExist());
		$this->assertEquals('DeepValue', $bindable->getValue());

		$path = new AttributePath(['level1', 'level2', 'levelX', 'level4', 'level5']);

		$bindable = $instance->createBindable($path, false);

		$this->assertFalse($bindable->doesExist());
		$this->assertNull($bindable->getValue());
	}
}

/* Dummy classes for testing purposes */
class DummyMix {
	public array $mix;
	public function __construct() {
		$this->mix = [
				'case1' => new Case1Parent(),
				'case2' => [
						'dm' => new \ArrayObject(['child' => new Case2Child('v2')])
				],
				'case3' => new Case3Parent(),
				'case4' => [
						'first' => [
								'dm' => new \ArrayObject(['key' => 'v4'])
						]
				],
				'case5' => new Case5Parent(),
				'case6' => new \ArrayObject(['arr' => ['child' => new Case6Child()]]),
				'case7' => new \ArrayObject(['dm' => new \ArrayObject(['child' => new Case7Child()])]),
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
	public \ArrayObject $arrObj;
	public function __construct() {
		$this->arrObj = new \ArrayObject(['arr' => ['val' => 'v1']]);
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
		$this->arr = ['info' => new \ArrayObject(['key' => 'v3'])];
	}
}

class Case5Child {
	public string $name;
	public function __construct() {
		$this->name = 'v5';
	}
}

class Case5Parent {
	public \ArrayObject $arrObj;
	public function __construct() {
		$this->arrObj = new \ArrayObject(['arr' => ['child' => new Case5Child()]]);
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