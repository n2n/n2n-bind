<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\build\impl\source\BindInstanceAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributePath;
use ReflectionException;
use n2n\util\ex\ExUtils;
use n2n\reflection\property\PropertyAccessException;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\UnknownPropertyException;

class ObjectBindInstance extends BindInstanceAdapter {


	/**
	 * @param object $object The source object from which properties are read.
	 */
	public function __construct(private object $object, private ObjectBindAccessProxyCache $proxyCache) {
		parent::__construct();
	}

	public function createBindable(AttributePath $path, bool $mustExist): Bindable {
		$propertyName = (string) $path;
		try {
			$refClass = ExUtils::try(fn() => new \ReflectionClass($this->object));
			$accessProxy = $this->proxyCache->getPropertyAccessProxy($refClass, $propertyName);
			$value = $accessProxy->getValue($this->object);

			$valueBindable = new ValueBindable($path, $value, true);
		} catch (PropertyAccessException $e) {
			throw new IllegalStateException(previous: $e);
		} catch (UnknownPropertyException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException('Could not resolve bindable: ' . $path->toAbsoluteString(), 0, $e);
			}
			$valueBindable = new ValueBindable($path, null, false);
		} catch (InaccessiblePropertyException|InvalidPropertyAccessMethodException $e) {
			throw new UnresolvableBindableException('Could not resolve bindable: ' . $path->toAbsoluteString(), 0, $e);
		}

		$this->addBindable($valueBindable);

		return $valueBindable;
	}
}