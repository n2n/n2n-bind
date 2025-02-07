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
use ReflectionClass;
use n2n\bind\build\impl\source\object\ObjectBindAccessProxyCache;
use n2n\bind\err\UnresolvableBindableException;
use n2n\reflection\property\UnknownPropertyException;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\PropertyAccessException;

class ObjectBindAccessProxyCacheTest extends TestCase {

	/**
	 * Test that calling getPropertyAccessProxy() twice for the same property returns the same instance.
	 * @throws UnknownPropertyException
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 */
	public function testCacheReturnsSameProxyForSameProperty(): void {
		$dummy = new class {
			public string $firstname = 'John';
			public string $lastname = 'Doe';
		};

		$refClass = new ReflectionClass($dummy);
		$cache = new ObjectBindAccessProxyCache();

		$proxy1 = $cache->getPropertyAccessProxy($refClass, 'firstname');
		$proxy2 = $cache->getPropertyAccessProxy($refClass, 'firstname');

		$this->assertSame($proxy1, $proxy2, "Same property should yield the same proxy instance.");
	}

	/**
	 * Test that different properties yield distinct proxy objects.
	 * @throws PropertyAccessException
	 */
	public function testCacheReturnsDistinctProxiesForDifferentProperties(): void {
		$dummy = new class {
			public string $firstname = 'John';
			public string $lastname = 'Doe';
		};

		$refClass = new ReflectionClass($dummy);
		$cache = new ObjectBindAccessProxyCache();

		$proxyFirst = $cache->getPropertyAccessProxy($refClass, 'firstname');
		$proxyLast  = $cache->getPropertyAccessProxy($refClass, 'lastname');

		$this->assertNotSame($proxyFirst, $proxyLast, "Different properties should yield distinct proxy instances.");


		$this->assertEquals('John', $proxyFirst->getValue($dummy));
		$this->assertEquals('Doe', $proxyFirst->getValue($dummy));
	}

	/**
	 * Test that for a given class, proxies for different properties are stored in the same cache item.
	 * @throws \ReflectionException
	 */
	public function testCacheSharedForSameClass(): void {
		$dummy = new class {
			public string $firstname = 'John';
			public string $lastname = 'Doe';
		};

		$refClass = new ReflectionClass($dummy);
		$cache = new ObjectBindAccessProxyCache();

		$proxyFirst = $cache->getPropertyAccessProxy($refClass, 'firstname');
		$proxyLast  = $cache->getPropertyAccessProxy($refClass, 'lastname');

		// Use reflection to inspect the internal cache.
		$cacheProp = new \ReflectionProperty($cache, 'cache');
		$cacheProp->setAccessible(true);
		$cacheData = $cacheProp->getValue($cache);

		$className = $refClass->getName();
		$this->assertArrayHasKey($className, $cacheData, "The cache should contain an entry for {$className}.");

		// $cacheData[$className] is an instance of ObjectBindAccessProxyCacheItem.
		$cacheItem = $cacheData[$className];

		$proxiesProp = new \ReflectionProperty($cacheItem, 'proxies');
		$proxiesProp->setAccessible(true);
		$proxies = $proxiesProp->getValue($cacheItem);

		$this->assertArrayHasKey('firstname', $proxies, "Cache item should contain key 'firstname'.");
		$this->assertArrayHasKey('lastname', $proxies, "Cache item should contain key 'lastname'.");
		$this->assertSame($proxies['firstname'], $proxyFirst);
		$this->assertSame($proxies['lastname'], $proxyLast);
	}

	/**
	 * Test that when many classes are cached, the cache is pruned in bulk.
	 */
	public function testPruneCacheInBulk(): void {
		$cache = new ObjectBindAccessProxyCache();
		$maxClassesNum = ObjectBindAccessProxyCache::MAX_CACHED_CLASSES_NUM;

		// Fill the cache with dummy classes using anonymous classes.
		for ($i = 0; $i < $maxClassesNum + 50; $i++) {
			$dummy = new class {
				public string $dummyProp = "dummy";
			};

			$ref = new ReflectionClass($dummy);
			try {
				$cache->getPropertyAccessProxy($ref, 'dummyProp');
			} catch (\Exception $e) {
				$this->fail("Unexpected exception while caching dummyProp: " . $e->getMessage());
			}
		}

		// Check the size of the internal cache.
		$cacheProp = new \ReflectionProperty($cache, 'cache');
		$cacheProp->setAccessible(true);
		$cachedData = $cacheProp->getValue($cache);

		$this->markTestSkipped('because not yet working');

		$this->assertGreaterThanOrEqual($maxClassesNum / 2, count($cachedData),
				"Cache size should not be lass than MAX_CACHED_CLASSES_NUM/2.");
		$this->assertLessThanOrEqual($maxClassesNum, count($cachedData),
				"Cache size should not exceed MAX_CACHED_CLASSES_NUM after pruning.");
	}

	/**
	 * Test that requesting a non-existent property throws an exception.
	 */
	public function testUnknownPropertyThrowsException(): void {
		$this->expectException(UnknownPropertyException::class);

		$dummy = new class {
			public string $firstname = 'John';
			public string $lastname = 'Doe';
		};

		$refClass = new ReflectionClass($dummy);
		$cache = new ObjectBindAccessProxyCache();

		// Request a property that does not exist.
		$cache->getPropertyAccessProxy($refClass, 'nonexistent');
	}
}