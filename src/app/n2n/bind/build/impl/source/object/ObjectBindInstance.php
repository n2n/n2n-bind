<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\build\impl\source\BindInstanceAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributePath;
use ReflectionException;

class ObjectBindInstance extends BindInstanceAdapter {

	/**
	 * Shared proxy cache for object property access.
	 */
	private ?ObjectBindAccessProxyCache $proxyCache = null;

	/**
	 * @param object $object The source object from which properties are read.
	 */
	public function __construct(private object $object) {
		parent::__construct();
		if ($this->proxyCache === null) {
			$this->proxyCache = new ObjectBindAccessProxyCache();
		}
	}

	public function createBindable(AttributePath $path, bool $mustExist): Bindable {
		$propertyName = (string)$path;
		try {
			$refClass = new \ReflectionClass($this->object);
			$accessProxy = $this->proxyCache->getPropertyAccessProxy($refClass, $propertyName);
			$value = $accessProxy->getValue($this->object);

			$value = is_callable($value) ? $value() : $value;
			$valueBindable = new ValueBindable($path, $value, true);
		} catch (ReflectionException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException('Could not resolve bindable: ' . $path->toAbsoluteString(), 0, $e);
			}
			$valueBindable = new ValueBindable($path, null, false);
		}
		$this->addBindable($valueBindable);
		return $valueBindable;
	}
}