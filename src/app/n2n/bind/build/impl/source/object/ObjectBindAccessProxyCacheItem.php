<?php
namespace n2n\bind\build\impl\source\object;

use ReflectionClass;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\reflection\property\UnknownPropertyException;
use n2n\bind\err\UnresolvableBindableException;

class ObjectBindAccessProxyCacheItem {
	private PropertiesAnalyzer $analyzer;

	/**
	 * Keys are propertyNames
	 *
	 * @var array<string, PropertyAccessProxy>
	 */
	private array $proxies = [];

	public function __construct(ReflectionClass $refClass) {
		$this->analyzer = new PropertiesAnalyzer($refClass, superIgnored: false);
	}

	/**
	 * Returns the proxy for a given property name if it exists in this cache item.
	 */
	public function getProxy(string $propertyName): PropertyAccessProxy {
		if (isset($this->proxies[$propertyName])) {
			return $this->proxies[$propertyName];
		}

		try {
			$proxy = $this->analyzer->analyzeProperty($propertyName, true, true);
			$this->setProxy($propertyName, $proxy);
			return $proxy;
		} catch (UnknownPropertyException|\ReflectionException $e) {
			throw new UnresolvableBindableException('Could not access '
					. $this->analyzer->getClass()->getName() . '::$' . $propertyName . '.', null, $e);
		}
	}

	/**
	 * Stores the given proxy for the property.
	 */
	public function setProxy(string $propertyName, PropertyAccessProxy $proxy): void {
		$this->proxies[$propertyName] = $proxy;
	}
}