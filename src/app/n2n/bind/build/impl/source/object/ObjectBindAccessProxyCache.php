<?php
namespace n2n\bind\build\impl\source\object;

use ReflectionClass;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\ex\ExUtils;

class ObjectBindAccessProxyCache {
	public const MAX_CACHED_CLASSES_NUM = 1000;

	/**
	 * Keys are classNames
	 * @var array<string, ObjectBindAccessProxyCacheItem>
	 */
	private array $cache = [];

	/**
	 * Returns a PropertyAccessProxy for the given property.
	 *
	 * @param ReflectionClass|string $class A ReflectionClass instance or a fully qualified class name.
	 * @param string $propertyName The property name.
	 * @return PropertyAccessProxy
	 * @throws UnresolvableBindableException If the property cannot be resolved.
	 */
	public function getPropertyAccessProxy(ReflectionClass|string $class, string $propertyName): PropertyAccessProxy {
		[$className, $refClass] = $this->resolveClass($class);

		if (!isset($this->cache[$className])) {
			$this->cache[$className] = new ObjectBindAccessProxyCacheItem($refClass);
		}

		$proxy = $this->cache[$className]->getProxy($propertyName);
		$this->pruneCacheIfNeeded();
		return $proxy;
	}

	/**
	 * Resolves the class name and ReflectionClass instance.
	 *
	 * @param ReflectionClass|string $class
	 * @return array{0: string, 1: ReflectionClass}
	 */
	private function resolveClass(ReflectionClass|string $class): array {
		if (is_string($class)) {
			$className = $class;
			$refClass = ExUtils::try(fn() => new ReflectionClass($class));
		} else {
			$refClass = $class;
			$className = $refClass->getName();
		}
		return [$className, $refClass];
	}

	/**
	 * Prunes the cache if it exceeds the maximum allowed number of classes.
	 */
	private function pruneCacheIfNeeded(): void {
		$cacheCount = count($this->cache);
		if ($cacheCount >= self::MAX_CACHED_CLASSES_NUM) {
			$numToKeep = (int)(self::MAX_CACHED_CLASSES_NUM / 2);
			$this->cache = array_slice($this->cache, -$numToKeep, null, true);
		}
	}
}