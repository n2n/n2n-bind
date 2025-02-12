<?php
namespace n2n\bind\build\impl\source\object;

use ReflectionClass;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\UnknownPropertyException;

class ObjectBindAccessProxyCache {
	public const MAX_CACHED_CLASSES_NUM = 200;

	/**
	 * Keys are classNames
	 * @var array<string, ObjectBindAccessProxyCacheItem>
	 */
	private array $cacheItems = [];

	/**
	 * Returns a PropertyAccessProxy for the given property.
	 *
	 * @param ReflectionClass $class A ReflectionClass instance or a fully qualified class name.
	 * @param string $propertyName The property name.
	 * @return PropertyAccessProxy
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws UnknownPropertyException
	 */
	public function getPropertyAccessProxy(ReflectionClass $class, string $propertyName): PropertyAccessProxy {
		$refClass = $class;
		$className = $refClass->getName();

		if (!isset($this->cacheItems[$className])) {
			$this->cacheItems[$className] = new ObjectBindAccessProxyCacheItem($refClass);
		}

		$proxy = $this->cacheItems[$className]->getProxy($propertyName);
		$this->pruneCacheIfNeeded();
		return $proxy;
	}

	/**
	 * Prunes the cache if it exceeds the maximum allowed number of classes.
	 */
	private function pruneCacheIfNeeded(): void {
		$cacheCount = count($this->cacheItems);
		if ($cacheCount >= self::MAX_CACHED_CLASSES_NUM) {
			$numToKeep = (int) (self::MAX_CACHED_CLASSES_NUM / 2);
			$this->cacheItems = array_slice($this->cacheItems, -$numToKeep, null, true);
		}
	}
}