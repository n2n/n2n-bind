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
use n2n\reflection\property\UnknownPropertyException;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\PropertyAccessException;
use n2n\util\ex\ExUtils;
use n2n\reflection\property\UninitializedBehaviour;

class ObjectBindAccessProxyCacheTest extends TestCase {

	/**
	 * Test that calling getPropertyAccessProxy() twice for the same property returns the same instance.
	 * @throws UnknownPropertyException
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 */
	public function testCacheReturnsSameProxyForSameProperty(): void {
		$dummy = $this->createUniqueDummy();

		$refClass = ExUtils::try(fn () => new ReflectionClass($dummy));
		$cache = new ObjectBindAccessProxyCache();

		$proxy1 = $cache->getPropertyAccessProxy($refClass, 'firstname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
		$proxy2 = $cache->getPropertyAccessProxy($refClass, 'firstname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);

		$this->assertSame($proxy1, $proxy2, "Same property should yield the same proxy instance.");
	}

	/**
	 * Test that different properties yield distinct proxy objects.
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws UnknownPropertyException
	 */
	public function testCacheReturnsDistinctProxiesForDifferentProperties(): void {
		$dummy = $this->createUniqueDummy();
		$refClass = ExUtils::try(fn () => new ReflectionClass($dummy));
		$cache = new ObjectBindAccessProxyCache();

		$proxyFirst = $cache->getPropertyAccessProxy($refClass, 'firstname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
		$proxyLast  = $cache->getPropertyAccessProxy($refClass, 'lastname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);

		$this->assertNotSame($proxyFirst, $proxyLast, "Different properties should yield distinct proxy instances.");

		$this->assertEquals('Testerich', $proxyFirst->getValue($dummy));
		$this->assertEquals('von Testen', $proxyLast->getValue($dummy));
	}

	/**
	 * Test that for a given class, proxies for different properties are stored in the same cache item.
	 * @throws \ReflectionException
	 */
	public function testCacheSharedForSameClass(): void {
		$refClass = new ReflectionClass($this->createUniqueDummy());
		$cache = new ObjectBindAccessProxyCache();

		$proxyFirst = $cache->getPropertyAccessProxy($refClass, 'firstname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
		$proxyLast  = $cache->getPropertyAccessProxy($refClass, 'lastname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);

		$cacheProp = new \ReflectionProperty($cache, 'cacheItems');
		$cacheData = $cacheProp->getValue($cache);

		$className = $refClass->getName();
		$this->assertArrayHasKey($className, $cacheData, "The cache should contain an entry for {$className}.");

		$cacheItem = $cacheData[$className];
		$proxiesProp = new \ReflectionProperty($cacheItem, 'proxies');
		$proxies = $proxiesProp->getValue($cacheItem);

		$this->assertArrayHasKey('firstname', $proxies, "Cache item should contain key 'firstname'.");
		$this->assertArrayHasKey('lastname', $proxies, "Cache item should contain key 'lastname'.");
		$this->assertSame($proxies['firstname'], $proxyFirst);
		$this->assertSame($proxies['lastname'], $proxyLast);
	}

	/**
	 * Test that when many classes are cached, the cache is pruned in bulk.
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws UnknownPropertyException
	 */
	public function testPruneCacheInBulk(): void {
		$cache = new ObjectBindAccessProxyCache();
		$maxClassesNum = ObjectBindAccessProxyCache::MAX_CACHED_CLASSES_NUM;

		for ($i = 0; $i < $maxClassesNum + 50; $i++) {
			$dummy = $this->createUniqueDummy();
			$ref = ExUtils::try(fn () => new ReflectionClass($dummy));
			$cache->getPropertyAccessProxy($ref, 'firstname', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
		}

		$cacheProp = ExUtils::try(fn () => new \ReflectionProperty($cache, 'cacheItems'));
		$cachedData = $cacheProp->getValue($cache);

		$this->assertCount(($maxClassesNum / 2) + 50, $cachedData,
				"Cache size should be lass than MAX_CACHED_CLASSES_NUM/2.");
	}

	private function createUniqueDummy(): object {
		$className = 'UniqueDummy' . uniqid();
		$code = 'class ' . $className . ' {
			public string $firstname = \'Testerich\';
			public string $lastname = \'von Testen\';
			public function __construct() {
			}
		}';
		eval($code);
		return new $className();
	}

	/**
	 * Test that requesting a non-existent property throws an exception.
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws UnknownPropertyException
	 */
	public function testUnknownPropertyThrowsException(): void {
		$this->expectException(UnknownPropertyException::class);
		$refClass = new ReflectionClass(new class {});
		$cache = new ObjectBindAccessProxyCache();
		$cache->getPropertyAccessProxy($refClass, 'nonexistent', UninitializedBehaviour::RETURN_UNDEFINED_IF_UNDEFINABLE);
	}
}