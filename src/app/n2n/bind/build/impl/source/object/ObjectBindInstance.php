<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\build\impl\source\BindInstanceAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\impl\ValueBindable;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributePath;
use n2n\reflection\property\PropertyAccessException;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\reflection\property\UnknownPropertyException;
use n2n\bind\err\BindException;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeConstraints;
use ArrayAccess;

class ObjectBindInstance extends BindInstanceAdapter {
	/**
	 * @param object $object The source object from which properties are read.
	 */
	public function __construct(private object $object, private ObjectBindAccessProxyCache $proxyCache) {
		parent::__construct();
	}

	/**
	 * @throws UnresolvableBindableException
	 */
	public function createBindable(AttributePath $path, bool $mustExist): Bindable {
//		if ($path->isEmpty()) {
//			$valueBindable = new ValueBindable($path, $this->object, doesExist: true);
//			$this->addBindable($valueBindable);
//			return $valueBindable;
//		}

		try {
			$value = $this->getValueByPath($path, $this->object);
			$valueBindable = new ValueBindable($path, $value, true);
		} catch (PropertyAccessException $e) {
			throw new IllegalStateException(previous: $e);
		} catch (UnknownPropertyException|InaccessiblePropertyException|InvalidPropertyAccessMethodException
				|\ReflectionException $e) {
			if ($mustExist) {
				throw new UnresolvableBindableException('Could not resolve bindable: '
						. $path->toAbsoluteString(), 0, $e);
			}
			$valueBindable = new ValueBindable($path, null, false);
		}

		$this->addBindable($valueBindable);
		return $valueBindable;
	}

	/**
	 * Recursively retrieves a nested value from a mixed data structure based on an AttributePath.
	 *
	 * This method supports traversing:
	 * - Arrays (via key lookup),
	 * - AttributeReader instances,
	 * - Objects (via reflection and the proxy cache).
	 *
	 * @param AttributePath $path The attribute path to traverse.
	 * @param object|array $data The data to traverse.
	 * @return mixed The nested value.
	 * @throws BindException If a segment cannot be found or if a nested value is not traversable.
	 * @throws \ReflectionException
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws UnknownPropertyException
	 */
	private function getValueByPath(AttributePath $path, object|array $data): mixed {
		$segments = $path->toArray();

		if (empty($segments)) {
			return $data;
		}

		$currentSegment = array_shift($segments);
		$value = $this->retrieveValueForSegment($currentSegment, $data);

		if (empty($segments)) {
			return $value;
		}

		if (!$this->isTraversableValue($value)) {
			throw new BindException("Nested value for '{$currentSegment}' is not traversable.");
		}

		$newPath = new AttributePath($segments);
		return $this->getValueByPath($newPath, $value);
	}

	/**
	 * Retrieves the value corresponding to a segment from the given data.
	 *
	 * @param string $segment The current segment.
	 * @param object|array $data The data to search.
	 * @return mixed The value for the given segment.
	 * @throws BindException If the segment cannot be found.
	 * @throws \ReflectionException
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws UnknownPropertyException
	 */
	private function retrieveValueForSegment(string $segment, object|array $data): mixed {
		if (is_array($data)) {
			return $this->getValueFromArray($segment, $data);
		} elseif ($data instanceof \ArrayAccess) {
			return $this->getValueFromArrayAccess($segment, $data);
		} elseif (is_object($data)) {
			return $this->getValueFromObject($segment, $data);
		}

		throw new BindException("Cannot traverse data of type " . gettype($data));
	}

	/**
	 * Retrieves a value from an array by key.
	 *
	 * @param string $segment The key.
	 * @param array $data The array.
	 * @return mixed The value at the given key.
	 * @throws UnresolvableBindableException
	 */
	private function getValueFromArray(string $segment, array $data): mixed {
		if (!array_key_exists($segment, $data)) {
			throw new UnresolvableBindableException("Key '{$segment}' not found in array.");
		}
		return $data[$segment];
	}

	/**
	 * Retrieves a value from an AttributeReader (or DataMap) using its req() method.
	 *
	 * @param string $segment The key.
	 * @param AttributeReader $arrayAccess The reader.
	 * @return mixed The value for the given key.
	 */
	private function getValueFromArrayAccess(string $segment, ArrayAccess $arrayAccess, bool $traversableRequired): mixed {
		$value = $arrayAccess->offsetGet($segment);

		if (!$traversableRequired || $this->isTraversableValue($value)) {
			return $value;
		}

		throw new ValueNotTraversableException('Key of ' . get_class($arrayAccess) . ' contains untraversable value of type: '
				. TypeUtils::getTypeInfo($value) . ', type of object|array|ArrayAccess required';
		return $arrayAccess->req(new AttributePath([$segment]));
	}

	/**
	 * Retrieves a value from an object using reflection and the proxy cache.
	 *
	 * @param string $segment The property name.
	 * @param object $object The object.
	 * @return mixed The property value.
	 * @throws \ReflectionException
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 * @throws PropertyAccessException
	 * @throws UnknownPropertyException
	 */
	private function getValueFromObject(string $segment, object $object, bool $traversableRequired): mixed {
		$refClass = new \ReflectionClass($object);
		$valueProxy = $this->proxyCache->getPropertyAccessProxy($refClass, $segment);

		if ($traversableRequired) {
			$valueProxy = $valueProxy->createRestricted(TypeConstraints::type(['object', 'array', ArrayAccess::class]));
		}

		throw ValueNotTraversableException();

		return $valueProxy->getValue($object);
	}

	/**
	 * Checks whether the given value is traversable.
	 *
	 * A value is considered traversable if it is an array, an object, or an instance of AttributeReader.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private function isTraversableValue(mixed $value): bool {
		return is_array($value) || is_object($value) || ($value instanceof AttributeReader);
	}
}